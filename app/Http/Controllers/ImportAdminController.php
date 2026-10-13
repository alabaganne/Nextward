<?php

namespace App\Http\Controllers;

use App\Importing\OpportunityImporter;
use App\Importing\SourceHttp;
use App\Jobs\ImportOpportunitySource;
use App\Models\Company;
use App\Models\ImportRun;
use App\Models\ImportSource;
use App\Models\Internship;
use App\Models\OpportunitySource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ImportAdminController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'source_id' => 'nullable|integer|exists:import_sources,id',
            'state' => 'nullable|in:pending,review,published,closed,rejected',
            'page' => 'nullable|integer|min:1|max:10000',
        ]);
        $items = OpportunitySource::with(['importSource', 'internship.company.user'])
            ->when($filters['source_id'] ?? null, fn ($query, $source) => $query->where('import_source_id', $source))
            ->when($filters['state'] ?? null, fn ($query, $state) => $query->where('state', $state))
            ->latest()->paginate(15)->withQueryString();
        return Inertia::render('Imports/Index', [
            'sources' => ImportSource::withCount(['items', 'items as pending_count' => fn ($query) => $query->whereIn('state', ['pending', 'review'])])->orderBy('name')->get(),
            'runs' => ImportRun::with('importSource')->latest()->limit(20)->get(),
            'items' => $items->through(fn ($item) => [
                'id' => $item->id, 'source_id' => $item->import_source_id, 'source_name' => $item->importSource->name,
                'source_url' => $item->source_url, 'state' => $item->state, 'error' => $item->error,
                'last_verified_at' => $item->last_verified_at?->toIso8601String(),
                'normalized_data' => $item->normalized_data,
                'internship' => $item->internship ? [
                    'id' => $item->internship_id, 'title' => $item->internship->title,
                    'public_url' => $item->internship->public_url, 'publication_status' => $item->internship->publication_status,
                    'company' => $item->internship->company->display_name,
                    'remote_eligibility' => $item->internship->remote_eligibility,
                    'relocation' => $item->internship->relocation, 'visa_sponsorship' => $item->internship->visa_sponsorship,
                ] : null,
            ]),
            'filters' => $filters,
            'reports' => DB::table('opportunity_reports')->join('internships', 'internships.id', '=', 'opportunity_reports.internship_id')
                ->where('opportunity_reports.status', 'open')->select('opportunity_reports.*', 'internships.title')->orderBy('opportunity_reports.id')->limit(50)->get(),
            'claims' => DB::table('company_claims')->join('users', 'users.id', '=', 'company_claims.user_id')->leftJoin('companies', 'companies.id', '=', 'company_claims.company_id')
                ->where('company_claims.status', 'pending')->select('company_claims.*', 'users.name as claimant_name', 'users.email as claimant_email', 'companies.name as company_name')->limit(30)->get(),
            'deliveryHealth' => DB::table('opportunity_deliveries')->selectRaw('status, count(*) as total')->groupBy('status')->get(),
            'uncertainDeliveries' => DB::table('opportunity_deliveries')->where('status', 'uncertain')->select('batch_id', 'saved_search_id', 'last_error', 'attempted_at')->distinct()->limit(20)->get(),
        ]);
    }

    public function storeSource(Request $request)
    {
        $source = ImportSource::create($this->sourceData($request));
        $this->audit($request, 'source.created', null, $source->id, ['permission_status' => $source->permission_status]);
        return back()->with('toast', ['type' => 'store', 'message' => 'Source configuration saved.']);
    }

    public function updateSource(Request $request, ImportSource $source)
    {
        $data = $this->sourceData($request, $source);
        DB::transaction(function () use ($source, $data, $request) {
            $source->update($data);
            if ($source->permission_status !== 'approved') {
                $roles = Internship::where('origin', 'imported')->whereHas('sources', fn ($query) => $query->where('import_source_id', $source->id))->get();
                foreach ($roles as $role) {
                    $hasApprovedSource = $role->sources()->where('state', 'published')->whereHas('importSource', fn ($query) => $query->where('permission_status', 'approved'))->exists();
                    if (! $hasApprovedSource) {
                        $role->update(['publication_status' => 'suppressed']);
                    }
                }
            }
            $this->audit($request, 'source.updated', null, $source->id, ['permission_status' => $source->permission_status, 'enabled' => $source->enabled]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => 'Source updated.']);
    }

    private function sourceData(Request $request, ?ImportSource $source = null): array
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9][a-z0-9_-]*$/', Rule::unique('import_sources')->ignore($source?->id)],
            'name' => 'required|string|max:255',
            'provider' => 'required|in:jsonld,greenhouse,lever,ashby',
            'url' => 'required|url:http,https|max:2048',
            'allowed_domains' => 'required|array|min:1|max:20',
            'allowed_domains.*' => 'required|string|max:253|regex:/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/',
            'permission_status' => 'required|in:pending,approved,revoked',
            'permission_notes' => 'nullable|required_if:permission_status,approved|string|min:20|max:10000',
            'publication_mode' => 'required|in:review,automatic',
            'enabled' => 'required|boolean',
            'company_id' => 'nullable|integer|exists:companies,id',
            'cadence_hours' => 'required|integer|min:1|max:168',
            'request_delay_ms' => 'required|integer|min:500|max:60000',
            'max_requests' => 'required|integer|min:1|max:1000',
            'settings' => 'nullable|array',
            'settings.employer_name' => 'nullable|string|max:255',
            'settings.job_path_prefix' => 'nullable|string|max:255|starts_with:/',
            'settings.authoritative_employer' => 'nullable|boolean',
        ]);
        $data['settings'] = collect($data['settings'] ?? [])->only(['employer_name', 'job_path_prefix', 'authoritative_employer'])->all();
        if ($source && ($source->url !== $data['url'] || $source->provider !== $data['provider'] || $source->allowed_domains !== $data['allowed_domains']) && $data['permission_status'] === 'approved' && $source->permission_notes === $data['permission_notes']) {
            throw ValidationException::withMessages(['permission_notes' => 'Record a fresh permission review when changing the source URL, provider, or allowed domains.']);
        }
        if ($data['enabled'] && $data['permission_status'] !== 'approved') {
            throw ValidationException::withMessages(['enabled' => 'A source must have recorded reuse approval before imports are enabled.']);
        }
        try {
            (new SourceHttp(new ImportSource($data)))->validate($data['url']);
        } catch (\Throwable $exception) {
            throw ValidationException::withMessages(['url' => $exception->getMessage()]);
        }
        return $data;
    }

    public function runSource(Request $request, ImportSource $source)
    {
        abort_unless($source->isApproved(), 422, 'Enable this source only after recording reuse approval.');
        $data = $request->validate(['recheck' => 'nullable|boolean']);
        ImportOpportunitySource::dispatch($source->id, 40, (bool) ($data['recheck'] ?? false));
        $this->audit($request, 'source.queued', null, $source->id, ['recheck' => (bool) ($data['recheck'] ?? false)]);
        return back()->with('toast', ['type' => 'update', 'message' => 'Import queued. Source limits and robots rules still apply.']);
    }

    public function publish(Request $request, OpportunitySource $item, OpportunityImporter $importer)
    {
        try {
            $opportunity = $importer->publish($item);
        } catch (\RuntimeException $exception) {
            throw ValidationException::withMessages(['publication' => $exception->getMessage()]);
        }
        $this->audit($request, 'opportunity.published', $opportunity->id, $item->import_source_id, ['item_id' => $item->id]);
        return back()->with('toast', ['type' => 'update', 'message' => 'Opportunity reviewed. Suppressed listings stay hidden until explicitly restored.']);
    }

    public function reject(Request $request, OpportunitySource $item)
    {
        $data = $request->validate(['reason' => 'required|string|min:5|max:1000']);
        DB::transaction(function () use ($item, $data, $request) {
            $item->update(['state' => 'rejected', 'error' => $data['reason']]);
            if ($item->internship && ! $item->internship->sources()->where('id', '!=', $item->id)->where('state', 'published')->exists()) {
                $item->internship->update(['publication_status' => 'suppressed']);
            }
            $this->audit($request, 'source-item.rejected', $item->internship_id, $item->import_source_id, ['item_id' => $item->id, 'reason' => $data['reason']]);
        });
        return back();
    }

    public function moderate(Request $request, Internship $internship)
    {
        $data = $request->validate([
            'action' => 'required|in:suppress,close,restore,correct',
            'reason' => 'required|string|min:5|max:2000',
            'corrections' => 'nullable|array',
            'corrections.title' => 'sometimes|required|string|max:255',
            'corrections.description' => 'sometimes|required|string|max:100000',
            'corrections.kind' => 'sometimes|required|in:internship,job',
            'corrections.country_code' => 'nullable|string|regex:/^[A-Z]{2}$/',
            'corrections.location' => 'nullable|string|max:255',
            'corrections.remote_eligibility' => 'sometimes|required|in:eligible,restricted,unknown',
            'corrections.relocation' => 'sometimes|required|in:yes,no,unknown',
            'corrections.visa_sponsorship' => 'sometimes|required|in:yes,no,unknown',
        ]);
        DB::transaction(function () use ($internship, $data, $request) {
            $internship = Internship::lockForUpdate()->findOrFail($internship->id);
            $overrides = $internship->moderation_overrides ?: [];
            if ($data['action'] === 'restore') {
                abort_if($internship->merged_into_id, 422, 'A merged duplicate cannot be restored.');
                if ($internship->origin === 'imported') {
                    abort_unless($internship->hasFreshSource(), 422, 'Restore requires a current approved source.');
                }
                unset($overrides['publication_status']);
                $internship->update(['publication_status' => 'published', 'moderation_overrides' => $overrides]);
            } elseif ($data['action'] === 'suppress') {
                $overrides['publication_status'] = 'suppressed';
                $internship->update(['publication_status' => 'suppressed', 'moderation_overrides' => $overrides]);
            } elseif ($data['action'] === 'close') {
                $overrides['status'] = 'closed';
                $internship->update(['status' => 'closed', 'moderation_overrides' => $overrides]);
            } else {
                $corrections = collect($data['corrections'] ?? [])->only(['title', 'description', 'kind', 'country_code', 'location', 'remote_eligibility', 'relocation', 'visa_sponsorship'])->all();
                abort_unless($corrections, 422, 'Choose a fact to correct.');
                $internship->update([...$corrections, 'moderation_overrides' => array_merge($overrides, $corrections)]);
            }
            $this->audit($request, 'opportunity.'.$data['action'], $internship->id, null, ['reason' => $data['reason'], 'corrections' => $data['corrections'] ?? []]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => 'Moderation saved. Import refreshes retain reviewed corrections.']);
    }

    public function resolveReport(Request $request, int $report)
    {
        $data = $request->validate(['status' => 'required|in:resolved,dismissed', 'notes' => 'required|string|min:5|max:2000']);
        $record = DB::table('opportunity_reports')->find($report);
        abort_unless($record, 404);
        DB::transaction(function () use ($request, $report, $record, $data) {
            DB::table('opportunity_reports')->where('id', $report)->update(['status' => $data['status'], 'updated_at' => now()]);
            $this->audit($request, 'report.'.$data['status'], $record->internship_id, null, ['report_id' => $report, 'notes' => $data['notes']]);
        });
        return back();
    }

    public function merge(Request $request, Internship $internship)
    {
        $data = $request->validate(['survivor_id' => 'required|integer|exists:internships,id', 'reason' => 'required|string|min:10|max:2000']);
        DB::transaction(function () use ($internship, $data, $request) {
            $ids = [$internship->id, (int) $data['survivor_id']];
            $roles = Internship::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $duplicate = $roles->get($internship->id);
            $survivor = $roles->get((int) $data['survivor_id']);
            abort_if($duplicate->id === $survivor->id || $duplicate->merged_into_id || $survivor->merged_into_id, 422, 'Select two distinct, unmerged opportunities.');
            abort_unless($duplicate->company_id === $survivor->company_id, 422, 'Verify and consolidate the employer identity before merging different companies.');
            abort_if($duplicate->origin === 'native' && $survivor->origin !== 'native', 422, 'Keep the employer-managed opportunity as the survivor.');
            foreach (DB::table('likes')->where('internship_id', $duplicate->id)->get() as $like) {
                DB::table('likes')->insertOrIgnore(['student_id' => $like->student_id, 'internship_id' => $survivor->id]);
            }
            DB::table('likes')->where('internship_id', $duplicate->id)->delete();
            DB::table('applications')->where('internship_id', $duplicate->id)->update(['internship_id' => $survivor->id, 'company_id' => $survivor->company_id]);
            OpportunitySource::where('internship_id', $duplicate->id)->update(['internship_id' => $survivor->id]);
            DB::table('opportunity_reports')->where('internship_id', $duplicate->id)->update(['internship_id' => $survivor->id]);
            $deliveryHistory = [];
            foreach (DB::table('opportunity_deliveries')->where('internship_id', $duplicate->id)->lockForUpdate()->get() as $delivery) {
                $existing = DB::table('opportunity_deliveries')->where('internship_id', $survivor->id)->where('user_id', $delivery->user_id)->lockForUpdate()->first();
                if (! $existing) {
                    DB::table('opportunity_deliveries')->where('id', $delivery->id)->update(['internship_id' => $survivor->id]);
                    continue;
                }
                $priority = ['sent' => 5, 'uncertain' => 4, 'sending' => 3, 'pending' => 2, 'cancelled' => 1];
                if (($priority[$delivery->status] ?? 0) > ($priority[$existing->status] ?? 0)) {
                    DB::table('opportunity_deliveries')->where('id', $existing->id)->update([
                        'status' => $delivery->status, 'sent_at' => $delivery->sent_at, 'batch_id' => $delivery->batch_id,
                        'saved_search_id' => $delivery->saved_search_id, 'attempts' => $delivery->attempts,
                        'attempted_at' => $delivery->attempted_at, 'last_error' => $delivery->last_error,
                    ]);
                }
                $deliveryHistory[] = ['removed_id' => $delivery->id, 'retained_id' => $existing->id, 'status' => $delivery->status, 'batch_id' => $delivery->batch_id];
                DB::table('opportunity_deliveries')->where('id', $delivery->id)->delete();
            }
            $duplicate->update(['merged_into_id' => $survivor->id, 'publication_status' => 'suppressed', 'status' => 'closed']);
            $this->audit($request, 'opportunity.merged', $survivor->id, null, ['duplicate_id' => $duplicate->id, 'reason' => $data['reason'], 'delivery_history' => $deliveryHistory]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => 'Duplicate merged. Saved roles, application history, and the old URL are preserved.']);
    }

    public function reviewClaim(Request $request, int $claim)
    {
        $data = $request->validate(['status' => 'required|in:approved,rejected', 'review_notes' => 'required|string|min:20|max:5000']);
        DB::transaction(function () use ($request, $claim, $data) {
            $record = DB::table('company_claims')->where('id', $claim)->lockForUpdate()->first();
            abort_unless($record && $record->status === 'pending', 404);
            if ($data['status'] === 'approved') {
                $claimant = User::with('userable')->findOrFail($record->user_id);
                abort_unless($claimant->isCompany(), 422, 'The claimant needs an existing employer account.');
                $company = Company::with('user')->lockForUpdate()->findOrFail($record->company_id);
                abort_if($company->user || $company->claimed_by_company_id || $company->id === $claimant->userable_id, 422, 'This company already has an account or a verified claim.');
                $target = $claimant->userable;
                DB::table('internships')->where('company_id', $company->id)->update(['company_id' => $target->id]);
                DB::table('applications')->where('company_id', $company->id)->update(['company_id' => $target->id]);
                ImportSource::where('company_id', $company->id)->update(['company_id' => $target->id]);
                $company->forceFill(['claimed_by_company_id' => $target->id])->save();
            }
            DB::table('company_claims')->where('id', $claim)->update([...$data, 'updated_at' => now()]);
            $this->audit($request, 'company-claim.'.$data['status'], null, null, ['claim_id' => $claim, 'company_id' => $record->company_id, 'review_notes' => $data['review_notes']]);
        });
        return back()->with('toast', ['type' => 'update', 'message' => 'Claim review saved. Imported applications remain external.']);
    }

    private function audit(Request $request, string $action, ?int $opportunity, ?int $source, array $details): void
    {
        DB::table('opportunity_audits')->insert([
            'user_id' => $request->user()->id, 'internship_id' => $opportunity, 'import_source_id' => $source,
            'action' => $action, 'details' => json_encode($details, JSON_THROW_ON_ERROR), 'created_at' => now(), 'updated_at' => now(),
        ]);
    }
}
