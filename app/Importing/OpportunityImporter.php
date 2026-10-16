<?php

namespace App\Importing;

use App\Ai\OpportunityClassifier;
use App\Models\Company;
use App\Models\Field;
use App\Models\ImportRun;
use App\Models\ImportSource;
use App\Models\Internship;
use App\Models\OpportunitySource;
use App\Models\Skill;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class OpportunityImporter
{
    // $onlyNew reads the source now and looks only at listings never checked before; $maxNew stops after that many new roles.
    // $focus is a full title regex: only matching listings are checked, the rest wait for the daily import.
    // $focusFields then keeps only listings sorted into those fields; the others also wait for the daily import.
    public function run(ImportSource $source, int $limit = 40, bool $dryRun = false, bool $recheck = false, bool $onlyNew = false, ?int $maxNew = null, ?string $focus = null, ?array $focusFields = null): array
    {
        if (! $source->isApproved()) { throw new RuntimeException('Source must be enabled with approved reuse permission.'); }
        if ($source->next_run_at?->isFuture() && ! $dryRun && ! $recheck && ! $onlyNew) {
            return ['status' => 'waiting', 'pending' => false];
        }
        $lock = Cache::lock('opportunity-import:'.$source->id, 350);
        if (! $dryRun && ! $lock->get()) { return ['status' => 'locked', 'pending' => false]; }
        $started = now(); $clock = microtime(true);
        $refreshBefore = $started->copy()->subHours($source->cadence_hours);
        $refreshOperator = '<=';
        if ($source->cadence_hours === 24) {
            // Daily verification belongs to a 05:00 Tunisia cycle, including later batches.
            $refreshBefore = $started->copy()->setTimezone('Africa/Tunis')->setTime(5, 0);
            if ($refreshBefore->gt($started)) { $refreshBefore->subDay(); }
            $refreshBefore->setTimezone(config('app.timezone', 'UTC'));
            $refreshOperator = '<';
        }
        $deadline = $clock + config('importer.max_run_seconds');
        $http = new SourceHttp($source, $deadline); $reader = new SourceReader($http);
        $normalizer = new OpportunityNormalizer();
        $classifier = app(OpportunityClassifier::class);
        $stats = ['status' => 'completed', 'complete' => false, 'discovered' => 0, 'processed' => 0, 'published' => 0, 'closed' => 0, 'rejected' => 0, 'requests' => 0];
        $run = null;
        try {
            if (! $dryRun) {
                // A worker killed mid-run leaves its record open, so close it before starting a new one.
                $source->runs()->where('status', 'running')->where('started_at', '<', $started->copy()->subMinutes(15))
                    ->update(['status' => 'failed', 'finished_at' => $started, 'error' => 'Run stopped before it finished.']);
                $run = ImportRun::create(['import_source_id' => $source->id, 'started_at' => $started]);
                $source->update(['last_run_at' => $started]);
            }
            // The full list is read once per cycle; follow-up batches only work through the saved backlog.
            $discover = $dryRun || $recheck || ! $source->last_discovered_at || $source->last_discovered_at->lt($refreshBefore)
                || ($onlyNew && $source->last_discovered_at->lt(now()->subMinutes(10)));
            $suspicious = false;
            if ($discover) {
                $snapshot = $reader->discover($source);
                $stats['discovered'] = count($snapshot['items']);
                $previous = $source->runs()->where('complete', true)->latest('id')->value('discovered');
                $suspicious = $previous > 0 && ($stats['discovered'] === 0 || ($previous >= 10 && $stats['discovered'] < $previous * .5));
                $stats['complete'] = $snapshot['complete'] && ! $suspicious;
                if ($suspicious) { $stats['status'] = 'needs_review'; }
            }

            if ($dryRun) {
                foreach (array_slice($snapshot['items'], 0, max(1, min($limit, 100))) as $discovery) {
                    $item = new OpportunitySource([...$discovery, 'first_seen_at' => $started]);
                    try {
                        $data = $normalizer->normalize($reader->read($source, $item));
                        $this->skipReason($source, $item, $data) ? $stats['rejected']++ : $stats['processed']++;
                    } catch (SourceBudgetException) {
                        break;
                    } catch (Throwable) { $stats['rejected']++; }
                    if (microtime(true) > $deadline) { break; }
                }
                return [...$stats, 'requests' => $http->requests, 'dry_run' => true];
            }

            // Persist the complete discovery backlog before limiting expensive detail fetches.
            foreach ($discover ? array_chunk($snapshot['items'], 200) : [] as $chunk) {
                $rows = array_map(fn ($item) => [
                    'import_source_id' => $source->id, 'external_id' => Str::limit($item['external_id'], 191, ''),
                    'source_url' => $item['source_url'], 'payload' => $item['payload'] ? json_encode($item['payload'], JSON_THROW_ON_ERROR) : null,
                    'state' => 'pending', 'first_seen_at' => $started, 'last_discovered_at' => $started,
                    'missing_runs' => 0, 'missing_since' => null, 'created_at' => $started, 'updated_at' => $started,
                ], $chunk);
                OpportunitySource::upsert($rows, ['import_source_id', 'external_id'], ['source_url', 'payload', 'last_discovered_at', 'missing_runs', 'missing_since', 'updated_at']);
            }
            if ($discover) { $source->update(['last_discovered_at' => $started]); }
            $cycle = $source->last_discovered_at;
            // These providers are read from the saved list payload, so only items still on the board are current.
            $items = $source->items()->where('state', '!=', 'rejected')
            ->when(in_array($source->provider, ['greenhouse', 'lever', 'ashby'], true), fn ($q) => $q->where('last_discovered_at', '>=', $cycle))
            ->when($onlyNew, fn ($query) => $query->whereNull('last_verified_at'), fn ($query) => $query->where(function ($query) use ($refreshBefore, $refreshOperator, $recheck) {
                $query->whereNull('last_verified_at')->orWhere('state', 'pending');
                $query->orWhere('last_verified_at', $refreshOperator, $recheck ? now()->addSecond() : $refreshBefore);
            }))->where(function ($query) use ($cycle) {
                $query->where('state', '!=', 'closed')->orWhere('last_discovered_at', '>=', $cycle);
            })->orderByRaw('CASE WHEN last_verified_at IS NULL THEN 0 ELSE 1 END')->orderBy('last_verified_at')->orderBy('id');
            $items = $focus
                ? $items->cursor()->filter(fn ($item) => $this->matchesFocus($item, $focus))->take(max(1, min($limit, 100)))->collect()
                : $items->limit(max(1, min($limit, 100)))->get();

            $newIds = [];
            foreach ($items as $item) {
                if (microtime(true) > $deadline || $http->requests >= $source->max_requests - 2 || ($maxNew && count($newIds) >= $maxNew)) { break; }
                try {
                    $data = $normalizer->normalize($reader->read($source, $item));
                    $item->update(['normalized_data' => $data, 'last_verified_at' => now(), 'error' => null, 'application_url' => $data['application_url']]);
                    $stats['processed']++;
                    if ($this->expired($data)) {
                        $this->closeSource($item);
                        $stats['closed']++;
                    } elseif (! $item->internship_id && ($reason = $this->skipReason($source, $item, $data))) {
                        $item->update(['state' => 'rejected', 'error' => $reason]);
                        $stats['rejected']++;
                    } elseif ($this->olderThan($data, $item, config('importer.retention_days'))) {
                        $this->closeSource($item);
                        $item->update(['state' => 'rejected', 'error' => 'Posted more than '.config('importer.retention_days').' days ago.']);
                        $stats['closed']++;
                    } elseif (! $item->internship_id && $item->content_hash) {
                        // It was published before and its listing was deleted since, so it stays out.
                        $item->update(['state' => 'rejected', 'error' => 'Added before and removed later.']);
                        $stats['rejected']++;
                    } else {
                        // Claude reads each listing once, before it is first published; the answer is kept for later refreshes.
                        if (! $item->ai_review && ! $item->internship_id && $classifier->enabled()) {
                            $item->ai_review = $classifier->classify($data, $this->fieldNames());
                        }
                        if ($item->ai_review) {
                            $data = $this->withReview($data, $item->ai_review);
                            $item->update(['ai_review' => $item->ai_review, 'normalized_data' => $data]);
                        }
                        if ($focusFields && ! $item->internship_id && ! $this->inFocus($data, $focusFields)) {
                            $stats['rejected']++;
                        } elseif ($source->publication_mode === 'automatic' || $item->internship_id) {
                            $role = $this->publish($item);
                            if ($role->wasRecentlyCreated) { $newIds[] = $role->id; }
                            $stats['published']++;
                        } else {
                            $item->update(['state' => 'review']);
                        }
                    }
                } catch (SourceBudgetException) {
                    break;
                } catch (SourceRequestException $e) {
                    if (in_array($e->status, [404, 410], true)) {
                        $this->closeSource($item); $stats['closed']++;
                    } else {
                        $item->update(['error' => $e->getMessage()]);
                        throw $e;
                    }
                } catch (\Illuminate\Database\QueryException $e) {
                    throw $e;
                } catch (Throwable $e) {
                    // A malformed record cannot close its existing public counterpart.
                    $item->update(['state' => 'review', 'error' => Str::limit($e->getMessage(), 1000)]);
                    $stats['rejected']++;
                }
            }
            if ($discover && $stats['complete'] && $snapshot['authoritative']) {
                $missing = $source->items()->where('last_discovered_at', '<', $started)->whereNotIn('state', ['closed', 'rejected'])->get();
                foreach ($missing as $item) {
                    $item->update(['missing_since' => $item->missing_since ?? $started, 'missing_runs' => $item->missing_runs + 1]);
                    if ($item->missing_runs >= 2 && $item->missing_since->lte(now()->subDay())
                        && (! $item->last_verified_at || $item->last_verified_at->lte($item->missing_since))) {
                        $this->closeSource($item); $stats['closed']++;
                    }
                }
            }
            $pending = $source->items()->where('last_discovered_at', '>=', $cycle)
                ->where('state', '!=', 'rejected')
                ->where(function ($q) use ($refreshBefore, $refreshOperator) { $q->whereNull('last_verified_at')->orWhere('last_verified_at', $refreshOperator, $refreshBefore); })
                ->whereNull('error')->exists();
            $error = $suspicious ? 'Inventory dropped unexpectedly. Review before treating this snapshot as complete.' : null;
            $next = now()->addHours($source->cadence_hours);
            if ($source->cadence_hours === 24) {
                $next = now('Africa/Tunis')->setTime(5, 0);
                if ($next->lte(now())) { $next->addDay(); }
                $next->setTimezone(config('app.timezone', 'UTC'));
            }
            $source->update(['last_complete_at' => $stats['complete'] ? now() : $source->last_complete_at,
                'next_run_at' => $pending ? now()->addSeconds(30) : $next, 'last_error' => $error]);
            $run->update([...$stats, 'requests' => $http->requests, 'finished_at' => now(), 'error' => $error]);
            return [...$stats, 'pending' => $pending, 'requests' => $http->requests, 'new' => $newIds];
        } catch (Throwable $e) {
            if (! $dryRun) {
                $message = Str::limit($e->getMessage(), 1500);
                $retry = $e instanceof SourceRequestException ? max(300, $e->retryAfter) : 900;
                $run?->update([...$stats, 'status' => 'failed', 'complete' => false, 'requests' => $http->requests, 'finished_at' => now(), 'error' => $message]);
                $recent = $source->runs()->latest('id')->limit(3)->pluck('status');
                $pause = ($recent->count() === 3 && $recent->every(fn ($status) => $status === 'failed')) || $retry > 31536000;
                $source->update(['last_error' => ($pause ? 'Source paused after repeated failures or an excessive retry delay. ' : '').$message,
                    'next_run_at' => now()->addSeconds(min($retry, 31536000)), 'enabled' => $pause ? false : $source->enabled]);
            }
            throw $e;
        } finally {
            if (! $dryRun) { $lock->release(); }
        }
    }

    public function publish(OpportunitySource $item): Internship
    {
        $source = $item->importSource;
        if (! $source->isApproved() || ! $item->normalized_data || ! $item->last_verified_at || $item->last_verified_at->lt(now()->subHours($source->freshnessHours()))) {
            throw new RuntimeException('Publishing requires an approved source and recently verified content.');
        }
        $data = $item->normalized_data;
        if ($this->expired($data)) { throw new RuntimeException('This opportunity has expired.'); }
        return DB::transaction(function () use ($item, $source, $data) {
            $company = $source->company ?? $this->employer($source, $data);
            if ($company->claimed_by_company_id) { $company = Company::findOrFail($company->claimed_by_company_id); }
            $opportunity = $item->internship;
            if (! $opportunity) {
                // A shared role-specific destination and exact employer/title are a strong duplicate signal.
                $candidates = OpportunitySource::where('application_url', $data['application_url'])->whereNotNull('internship_id')->with('internship.company.user')->get();
                foreach ($candidates as $candidate) {
                    if ($candidate->internship && $candidate->internship->title === $data['title']
                        && $candidate->internship->company->display_name === $data['employer']
                        && $candidate->internship->location === $data['location']
                        && $candidate->internship->country_code === $data['country_code']
                        && mb_strlen(parse_url($data['application_url'], PHP_URL_PATH) ?: '') > 8) {
                        $opportunity = $candidate->internship; break;
                    }
                }
            }
            $opportunity ??= $this->sameRoleOnOtherBoard($company, $item, $data) ?? new Internship();
            $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
            $hasEmployerSource = $opportunity->exists && $opportunity->sources()->where('id', '!=', $item->id)
                ->whereHas('importSource', fn ($q) => $q->where('settings->authoritative_employer', true))->exists();
            // When a role is listed on several boards, the first live listing owns its content.
            $isPrimary = ! $opportunity->exists || ! $opportunity->sources()->where('id', '<', $item->id)->where('state', 'published')->exists();
            if (! $opportunity->exists || ($opportunity->origin === 'imported' && ((! $hasEmployerSource && $isPrimary) || ($source->settings['authoritative_employer'] ?? false)))) {
                $fields = collect($data)->except(['employer', 'website', 'requisition_id'])->all();
                $fieldId = $this->classify($data);
                $overrides = $opportunity->moderation_overrides ?? [];
                foreach (array_keys($overrides) as $key) { unset($fields[$key]); }
                $existingStatus = $opportunity->publication_status;
                $fields += ['company_id' => $company->id, 'field_id' => $fieldId,
                    'city_id' => null, 'origin' => 'imported', 'application_mode' => 'external',
                    'first_seen_at' => $opportunity->first_seen_at ?? $item->first_seen_at];
                if (! $opportunity->exists || $item->content_hash !== $hash) { $fields['content_updated_at'] = now(); }
                $fields['publication_status'] = $existingStatus === 'suppressed' ? 'suppressed' : ($overrides['publication_status'] ?? 'published');
                if (! array_key_exists('status', $overrides)) { $fields['status'] = 'open'; }
                $fields['last_verified_at'] = now();
                // Preserve an already verified employer link when refreshing a merged source.
                if ($opportunity->exists) { unset($fields['company_id'], $fields['field_id'], $fields['city_id']); }
                $opportunity->fill($fields)->save();
                if (! array_key_exists('skills', $overrides)) { $opportunity->skills()->sync($this->skillIds($data['title'], $data['description'])); }
            }
            $item->update(['internship_id' => $opportunity->id, 'state' => 'published', 'content_hash' => $hash, 'error' => null]);
            return $opportunity;
        });
    }

    public function closeSource(OpportunitySource $item): void
    {
        $item->update(['state' => 'closed', 'last_verified_at' => now(), 'error' => null]);
        $role = $item->internship;
        if (! $role || $role->origin !== 'imported') { return; }
        $authoritative = $item->importSource->settings['authoritative_employer'] ?? false;
        $otherOpen = $role->sources()->with('importSource')->where('id', '!=', $item->id)->where('state', 'published')->get()
            ->contains(fn ($other) => $other->importSource?->permission_status === 'approved' && $other->last_verified_at
                && $other->last_verified_at->gte(now()->subHours($other->importSource->freshnessHours())));
        if ($authoritative || ! $otherOpen) { $role->update(['status' => 'closed', 'content_updated_at' => now()]); }
    }

    public function reconcile(): int
    {
        $count = 0;
        $cutoff = now()->subDays(config('importer.retention_days'));
        Internship::where('origin', 'imported')->where('status', 'open')->where(function ($q) use ($cutoff) {
            $q->where('posted_at', '<', $cutoff)->orWhere(fn ($q) => $q->whereNull('posted_at')->where('first_seen_at', '<', $cutoff));
        })->chunkById(100, function ($roles) use (&$count) {
            foreach ($roles as $role) { $role->update(['status' => 'closed']); $count++; }
        });
        Internship::where('status', 'open')->where(function ($q) {
            $q->where('expires_at', '<=', now())->orWhere(function ($q) {
                $q->whereNull('expires_at')->whereDate('closing_at', '<', now('Africa/Tunis')->toDateString());
            });
        })->chunkById(100, function ($roles) use (&$count) {
            foreach ($roles as $role) { $role->update(['status' => 'closed', 'content_updated_at' => now()]); $count++; }
        });
        // Stale is distinct from confirmed closed; automatic rechecks can restore these drafts.
        Internship::where('origin', 'imported')->where('publication_status', 'published')->where('status', 'open')
            ->with('sources.importSource')
            ->chunkById(100, function ($roles) use (&$count) {
                foreach ($roles as $role) {
                    if (! $role->hasFreshSource()) { $role->update(['publication_status' => 'draft']); $count++; }
                }
            });
        return $count;
    }

    private function employer(ImportSource $source, array $data): Company
    {
        $host = $data['website'] ? preg_replace('/^www\./', '', strtolower((string) parse_url($data['website'], PHP_URL_HOST))) : null;
        if ($source->provider !== 'jsonld') {
            $identity = $source->id.':'.hash('sha256', 'employer-board|'.$data['employer']);
        } elseif ($host) {
            $identity = 'domain:'.$host;
        } else {
            // Boards rarely give the employer site, so the same name in the same country is treated as one company.
            $identity = 'employer:'.hash('sha256', $this->normalizeName($data['employer']).'|'.($data['country_code'] ?? ''));
        }
        $company = Company::where('import_identity', $identity)->first()
            ?? ($host ? Company::where('domain', $host)->first() : null)
            ?? Company::whereRaw('LOWER(name) = ?', [mb_strtolower(trim($data['employer']))])->first();
        if (! $company) {
            $company = new Company();
            $company->forceFill(['import_identity' => $identity, 'name' => $data['employer'], 'website' => $data['website'],
                'domain' => $host, 'city_id' => null, 'country_code' => $data['country_code']])->save();
        }
        return $company;
    }

    private function sameRoleOnOtherBoard(Company $company, OpportunitySource $item, array $data): ?Internship
    {
        $title = $this->normalizeTitle($data['title']);
        return Internship::where('company_id', $company->id)->where('origin', 'imported')->where('kind', $data['kind'])
            ->where('status', 'open')->whereNull('merged_into_id')
            ->where('first_seen_at', '>=', now()->subDays(config('importer.retention_days')))
            ->whereDoesntHave('sources', fn ($q) => $q->where('import_source_id', $item->import_source_id))
            ->latest('id')->limit(50)->get()
            ->first(fn ($role) => $role->country_code === $data['country_code'] && $this->normalizeTitle($role->title) === $title);
    }

    private function normalizeName(string $name): string
    {
        $name = mb_strtolower(Str::ascii($name));
        $name = preg_replace('/\b(s\.?a\.?r\.?l|suarl|sa|sas|spa|inc|ltd|llc|gmbh|group|groupe)\b\.?/', ' ', $name);
        return Str::squish(preg_replace('/[^a-z0-9]+/', ' ', $name));
    }

    private function normalizeTitle(string $title): string
    {
        $title = mb_strtolower(Str::ascii($title));
        $title = preg_replace('~\((?:[hfm]\s*/\s*[hfm](?:\s*/\s*nb)?|nb)\)~', ' ', $title);
        return Str::squish(preg_replace('/[^a-z0-9]+/', ' ', $title));
    }

    // Checks the title before any page is fetched: board payloads carry it, and board job URLs end with a title slug.
    private function matchesFocus(OpportunitySource $item, string $focus): bool
    {
        $title = $item->payload['title'] ?? $item->payload['text'] ?? rawurldecode((string) parse_url($item->source_url, PHP_URL_PATH));
        $title = Str::ascii(mb_strtolower(str_replace(['-', '_', '/'], ' ', (string) $title)));
        return (bool) preg_match($focus, $title);
    }

    private function olderThan(array $data, OpportunitySource $item, int $days): bool
    {
        $posted = ! empty($data['posted_at']) ? Carbon::parse($data['posted_at']) : $item->first_seen_at;
        return $posted !== null && $posted->lt(now()->subDays($days));
    }

    // Returns why a newly found listing is not imported, or null when it should be.
    private function skipReason(ImportSource $source, OpportunitySource $item, array $data): ?string
    {
        if ($this->olderThan($data, $item, config('importer.max_age_days'))) {
            return 'Posted more than '.config('importer.max_age_days').' days ago.';
        }
        // Worldwide boards keep internships, plus jobs someone in Tunisia can actually take.
        if (($source->settings['scope'] ?? 'all') === 'reachable' && $data['kind'] === 'job'
            && $data['country_code'] !== 'TN' && $data['relocation'] !== 'yes' && $data['visa_sponsorship'] !== 'yes'
            && ! ($data['work_type'] === 'remote' && $data['remote_eligibility'] !== 'restricted')) {
            return 'Job is not open to candidates in Tunisia.';
        }
        return null;
    }

    private function fieldNames(): array
    {
        Field::firstOrCreate(['name' => 'Other']);
        return Field::orderBy('name')->pluck('name')->all();
    }

    // Claude's answers replace the keyword guesses; "unknown" keeps whatever the rules found.
    private function withReview(array $data, array $review): array
    {
        $data['kind'] = $review['kind'];
        $data['field'] = $review['field'];
        $data['is_software_role'] = $review['is_software_role'];
        if ($review['seniority'] !== 'unspecified') { $data['seniority'] = $review['seniority']; }
        if ($data['work_type'] === 'remote' && $review['remote_from_tunisia'] !== 'unknown') {
            $data['remote_eligibility'] = $review['remote_from_tunisia'];
            if ($review['evidence'] !== '') { $data['eligibility_evidence']['remote'] = Str::limit($review['evidence'], 400); }
        }
        foreach (['relocation', 'visa_sponsorship'] as $key) {
            if ($review[$key] !== 'unknown') {
                $data[$key] = $review[$key];
                if ($review['evidence'] !== '') { $data['eligibility_evidence'][$key] = Str::limit($review['evidence'], 400); }
            }
        }
        return $data;
    }

    private function inFocus(array $data, array $fields): bool
    {
        $field = $data['field'] ?? Field::find($this->classify($data))?->name;
        return in_array($field, $fields, true) && ($data['is_software_role'] ?? true);
    }

    // Only skills from importer.skills are tagged, so words like "product" in a description never become skills.
    public function skillIds(string $title, string $description): array
    {
        return array_map(fn ($name) => Skill::firstOrCreate(['name' => $name])->id, (new OpportunityNormalizer())->skills($title, $description));
    }

    private function classify(array $data): int
    {
        $title = mb_strtolower($data['title']);
        $description = mb_strtolower($data['description']);
        // Ordered from most to least specific: on a tie the earlier field wins.
        $patterns = [
            'Mobile Development' => 'mobile (?:develop|engineer|app)|(?:engineer|developer|intern)\\W+mobile|d[ée]velopp\\w* mobile|application mobile|android|ios\\b|flutter|react native|kotlin|swift',
            'Web Development' => 'web develop|d[ée]velopp\\w* web|front.?end|int[ée]grateur web|php|symfony|laravel|angular|react|vue(?:js)?|javascript|typescript|wordpress',
            'Software Engineering' => 'software|ing[ée]nieur (?:logiciel|d[ée]veloppement|informatique)|d[ée]veloppeur|developer|programm(?:er|eur)|back.?end|full.?stack|\\.net|java|spring|python|golang|c#|c\\+\\+',
            'Data Science & AI' => 'data scien|machine learning|intelligence artificielle|\\bai\\b|\\bia\\b|llm',
            'DevOps & Cloud' => 'devops|cloud engineer|site reliability',
            'Cybersecurity' => 'cybersecurity|cybersécurité|security engineer|sécurité informatique',
            'Embedded Systems' => 'embedded|embarqué|firmware',
            'Network & Systems' => 'network engineer|administrateur (?:système|réseau)|systems engineer',
            'UI/UX Design' => 'ux design|ui design|product design|designer ux|designer ui',
            'Business Intelligence' => 'business intelligence|bi analyst|analyste bi',
            'Digital Marketing' => 'digital marketing|marketing digital|seo\\b|r[ée]f[ée]renceur|growth marketing',
            'Finance & Accounting' => 'accountant|accounting|comptable|comptabilité|financial analyst|analyste financ',
        ];
        $fields = Field::where('name', '!=', 'Other')->get()->keyBy('name');
        $scores = [];
        foreach ([...$patterns, ...$fields->keys()->diff(array_keys($patterns))->mapWithKeys(fn ($name) => [$name => preg_quote(mb_strtolower($name), '/')])] as $name => $pattern) {
            if (! $fields->has($name)) { continue; }
            $score = preg_match('/(?<![\\pL\\pN])(?:'.$pattern.')/iu', $title) ? 3 : (preg_match('/(?<![\\pL\\pN])(?:'.$pattern.')/iu', $description) ? 1 : 0);
            if ($score) { $scores[$fields[$name]->id] = $score; }
        }
        arsort($scores);
        $best = $scores ? array_key_first($scores) : null;
        if (! empty($data['field'])) { $best = $fields->get($data['field'])?->id; }
        return $best ?: Field::firstOrCreate(['name' => 'Other'])->id;
    }

    private function expired(array $data): bool
    {
        return (! empty($data['expires_at']) && Carbon::parse($data['expires_at'])->lte(now()))
            || (! empty($data['closing_at']) && $data['closing_at'] < now('Africa/Tunis')->toDateString());
    }
}
