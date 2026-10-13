<?php

namespace App\Importing;

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
    public function run(ImportSource $source, int $limit = 40, bool $dryRun = false, bool $recheck = false): array
    {
        if (! $source->isApproved()) { throw new RuntimeException('Source must be enabled with approved reuse permission.'); }
        if ($source->next_run_at?->isFuture() && ! $dryRun && ! $recheck) {
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
        $http = new SourceHttp($source); $reader = new SourceReader($http);
        $normalizer = new OpportunityNormalizer();
        $stats = ['status' => 'completed', 'complete' => false, 'discovered' => 0, 'processed' => 0, 'published' => 0, 'closed' => 0, 'rejected' => 0, 'requests' => 0];
        $run = null;
        try {
            if (! $dryRun) {
                $run = ImportRun::create(['import_source_id' => $source->id, 'started_at' => $started]);
                $source->update(['last_run_at' => $started]);
            }
            $snapshot = $reader->discover($source);
            $stats['discovered'] = count($snapshot['items']);
            $previous = $source->runs()->where('complete', true)->latest('id')->value('discovered');
            $suspicious = $previous > 0 && ($stats['discovered'] === 0 || ($previous >= 10 && $stats['discovered'] < $previous * .5));
            $stats['complete'] = $snapshot['complete'] && ! $suspicious;
            if ($suspicious) { $stats['status'] = 'needs_review'; }

            if ($dryRun) {
                foreach (array_slice($snapshot['items'], 0, max(1, min($limit, 100))) as $discovery) {
                    $item = new OpportunitySource($discovery);
                    try {
                        $normalizer->normalize($reader->read($source, $item));
                        $stats['processed']++;
                    } catch (Throwable) { $stats['rejected']++; }
                    if (microtime(true) - $clock > config('importer.max_run_seconds')) { break; }
                }
                return [...$stats, 'requests' => $http->requests, 'dry_run' => true];
            }

            // Persist the complete discovery backlog before limiting expensive detail fetches.
            foreach (array_chunk($snapshot['items'], 200) as $chunk) {
                $rows = array_map(fn ($item) => [
                    'import_source_id' => $source->id, 'external_id' => Str::limit($item['external_id'], 191, ''),
                    'source_url' => $item['source_url'], 'payload' => $item['payload'] ? json_encode($item['payload'], JSON_THROW_ON_ERROR) : null,
                    'state' => 'pending', 'first_seen_at' => $started, 'last_discovered_at' => $started,
                    'missing_runs' => 0, 'missing_since' => null, 'created_at' => $started, 'updated_at' => $started,
                ], $chunk);
                OpportunitySource::upsert($rows, ['import_source_id', 'external_id'], ['source_url', 'payload', 'last_discovered_at', 'missing_runs', 'missing_since', 'updated_at']);
            }
            $items = $source->items()->where('state', '!=', 'rejected')
            ->when(in_array($source->provider, ['lever', 'ashby'], true), fn ($q) => $q->where('last_discovered_at', '>=', $started))
            ->where(function ($query) use ($refreshBefore, $refreshOperator, $recheck) {
                $query->whereNull('last_verified_at')->orWhere('state', 'pending');
                $query->orWhere('last_verified_at', $refreshOperator, $recheck ? now()->addSecond() : $refreshBefore);
            })->where(function ($query) use ($started) {
                $query->where('state', '!=', 'closed')->orWhere('last_discovered_at', '>=', $started);
            })->orderByRaw('CASE WHEN last_verified_at IS NULL THEN 0 ELSE 1 END')->orderBy('last_verified_at')->orderBy('id')
                ->limit(max(1, min($limit, 100)))->get();

            foreach ($items as $item) {
                if (microtime(true) - $clock > config('importer.max_run_seconds') || $http->requests >= $source->max_requests - 2) { break; }
                try {
                    $data = $normalizer->normalize($reader->read($source, $item));
                    $item->update(['normalized_data' => $data, 'last_verified_at' => now(), 'error' => null, 'application_url' => $data['application_url']]);
                    $stats['processed']++;
                    if ($this->expired($data)) {
                        $this->closeSource($item);
                        $stats['closed']++;
                    } elseif ($source->publication_mode === 'automatic' || $item->internship_id) {
                        $this->publish($item);
                        $stats['published']++;
                    } else {
                        $item->update(['state' => 'review']);
                    }
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
            if ($stats['complete'] && $snapshot['authoritative']) {
                $missing = $source->items()->where('last_discovered_at', '<', $started)->whereNotIn('state', ['closed', 'rejected'])->get();
                foreach ($missing as $item) {
                    $item->update(['missing_since' => $item->missing_since ?? $started, 'missing_runs' => $item->missing_runs + 1]);
                    if ($item->missing_runs >= 2 && $item->missing_since->lte(now()->subDay())
                        && (! $item->last_verified_at || $item->last_verified_at->lte($item->missing_since))) {
                        $this->closeSource($item); $stats['closed']++;
                    }
                }
            }
            $pending = $source->items()->where('last_discovered_at', '>=', $started)
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
            return [...$stats, 'pending' => $pending, 'requests' => $http->requests];
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
            $company = $source->company;
            if (! $company) {
                // An unverified employer name is never a global company-merge key.
                $employerIdentity = $source->provider === 'jsonld' ? ($data['website'] ?? $item->external_id) : 'employer-board';
                $identity = $source->id.':'.hash('sha256', $employerIdentity.'|'.$data['employer']);
                $company = Company::where('import_identity', $identity)->first();
                if (! $company) {
                    $company = new Company();
                    $company->forceFill(['import_identity' => $identity, 'name' => $data['employer'], 'website' => $data['website'],
                        'domain' => $data['website'] ? parse_url($data['website'], PHP_URL_HOST) : null,
                        'city_id' => null, 'country_code' => $data['country_code']])->save();
                }
            }
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
            $opportunity ??= new Internship();
            $hash = hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
            $hasEmployerSource = $opportunity->exists && $opportunity->sources()->where('id', '!=', $item->id)
                ->whereHas('importSource', fn ($q) => $q->where('settings->authoritative_employer', true))->exists();
            if (! $opportunity->exists || ($opportunity->origin === 'imported' && (! $hasEmployerSource || ($source->settings['authoritative_employer'] ?? false)))) {
                $fields = collect($data)->except(['employer', 'website', 'requisition_id'])->all();
                [$fieldId, $skillIds] = $this->classify($data);
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
                if (! array_key_exists('skills', $overrides)) { $opportunity->skills()->sync($skillIds); }
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

    private function classify(array $data): array
    {
        $title = mb_strtolower($data['title']);
        $description = mb_strtolower($data['description']);
        $patterns = [
            'Software Engineering' => 'software engineer|ingénieur logiciel|développeur logiciel|backend|full.stack',
            'Web Development' => 'web develop|développ.{0,12}web|frontend|front.end',
            'Mobile Development' => 'mobile develop|développ.{0,12}mobile|android|ios developer',
            'Data Science & AI' => 'data scien|machine learning|intelligence artificielle',
            'Cybersecurity' => 'cybersecurity|cybersécurité|security engineer|sécurité informatique',
            'Network & Systems' => 'network engineer|administrateur (?:système|réseau)|systems engineer',
            'UI/UX Design' => 'ux design|ui design|product design|designer ux|designer ui',
            'DevOps & Cloud' => 'devops|cloud engineer|site reliability',
            'Embedded Systems' => 'embedded|embarqué|firmware',
            'Business Intelligence' => 'business intelligence|bi analyst|analyste bi',
            'Digital Marketing' => 'digital marketing|marketing digital|seo specialist|growth marketing',
            'Finance & Accounting' => 'accountant|accounting|comptable|comptabilité|financial analyst|analyste financ',
        ];
        $scores = [];
        foreach (Field::where('name', '!=', 'Other')->get() as $field) {
            $pattern = $patterns[$field->name] ?? preg_quote(mb_strtolower($field->name), '/');
            $score = preg_match('/'.$pattern.'/iu', $title) ? 3 : (preg_match('/'.$pattern.'/iu', $description) ? 1 : 0);
            if ($score) { $scores[$field->id] = $score; }
        }
        arsort($scores);
        $best = $scores ? array_key_first($scores) : null;
        if ($best && count(array_filter($scores, fn ($score) => $score === $scores[$best])) > 1) { $best = null; }
        $skills = Skill::all()->filter(fn ($skill) => mb_strlen($skill->name) >= 3
            && preg_match('/(?<![\pL\pN])'.preg_quote($skill->name, '/').'(?![\pL\pN])/iu', $title.' '.$description))->take(20)->pluck('id')->all();
        return [$best ?: Field::firstOrCreate(['name' => 'Other'])->id, $skills];
    }

    private function expired(array $data): bool
    {
        return (! empty($data['expires_at']) && Carbon::parse($data['expires_at'])->lte(now()))
            || (! empty($data['closing_at']) && $data['closing_at'] < now('Africa/Tunis')->toDateString());
    }
}
