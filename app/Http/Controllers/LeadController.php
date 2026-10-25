<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ContactFinder;
use App\Ai\OpportunityClassifier;
use App\Leads\LeadImport;
use App\Models\Candidate;
use App\Models\Lead;
use App\Models\LeadContact;
use App\Models\LeadSkip;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Throwable;

// The owner's job leads: the daily list, every lead with its status and notes, the jobs left out, and contacts.
class LeadController extends Controller
{
    private const DATE_SOURCES = ['ats' => 'employer job board', 'jsonld' => 'employer site', 'board' => 'job board date, not checked', 'seen' => 'first seen on Nextward'];

    public function index(Request $request)
    {
        $candidate = $this->candidate($request);
        $filters = $request->validate([
            'tab' => 'nullable|in:today,all,skipped',
            'q' => 'nullable|string|max:120',
            'eligibility' => 'nullable|in:remote,visa,unclear,excluded',
            'stage' => 'nullable|in:startup,established',
            'status' => 'nullable|in:'.implode(',', Lead::STATUSES),
            'reason' => 'nullable|in:repost,ineligible,seniority,years,duplicate,applied,cooldown,low_score,cap',
        ]) + ['tab' => 'today'];
        $search = trim((string) ($filters['q'] ?? ''));
        $day = Lead::where('candidate_id', $candidate->id)->max('digest_date');
        $day = $day ? substr((string) $day, 0, 10) : null;

        if ($filters['tab'] === 'skipped') {
            $skips = LeadSkip::where('candidate_id', $candidate->id)
                ->when($filters['reason'] ?? null, fn ($query, $reason) => $query->where('reason', $reason))
                ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('company_name', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")))
                ->latest('id')->paginate(50)->withQueryString()->through(fn ($skip) => $this->skip($skip));
        }
        $leads = $filters['tab'] === 'skipped' ? null : Lead::where('candidate_id', $candidate->id)->with('company.user')
            ->when($filters['tab'] === 'today', fn ($query) => $query->whereDate('digest_date', $day ?? today()))
            ->when($filters['eligibility'] ?? null, fn ($query, $value) => $query->where('eligibility', $value))
            ->when($filters['status'] ?? null, fn ($query, $value) => $query->where('status', $value))
            ->when($filters['stage'] ?? null, fn ($query, $value) => $query->whereHas('company', fn ($query) => $query->where('stage', $value)))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('company_name', 'like', "%{$search}%")->orWhere('title', 'like', "%{$search}%")->orWhere('notes', 'like', "%{$search}%")))
            ->orderByRaw($filters['tab'] === 'today' ? 'score DESC' : 'COALESCE(applied_at, created_at) DESC')->orderByDesc('id')
            ->paginate($filters['tab'] === 'today' ? 50 : 30)->withQueryString();
        if ($leads) {
            $contacts = LeadContact::where('candidate_id', $candidate->id)->whereIn('company_key', $leads->pluck('company_key')->unique())->orderBy('name')->get()->groupBy('company_key');
            $leads->through(fn ($lead) => $this->card($lead, $contacts));
        }

        return Inertia::render('Leads/Index', [
            'filters' => $filters,
            'day' => $day,
            'leads' => $leads,
            'skips' => $skips ?? null,
            'hidden' => $filters['tab'] === 'today' && $day ? LeadSkip::where('candidate_id', $candidate->id)->whereDate('run_date', $day)
                ->whereIn('reason', ['applied', 'cooldown'])->latest('id')->limit(50)->get()->map(fn ($skip) => $this->skip($skip)) : [],
            'counts' => Lead::where('candidate_id', $candidate->id)->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status'),
            'reasons' => LeadSkip::where('candidate_id', $candidate->id)->selectRaw('reason, COUNT(*) as total')->groupBy('reason')->pluck('total', 'reason'),
            'statuses' => Lead::STATUSES,
            'canSuggest' => app(OpportunityClassifier::class)->enabled(),
        ]);
    }

    // "I applied elsewhere" or a role to keep: merged with the lead for the same job when there is one.
    public function store(Request $request)
    {
        $data = $request->validate([
            'company' => 'required|string|max:191', 'title' => 'required|string|max:255', 'url' => 'nullable|url|max:2000',
            'status' => 'required|in:'.implode(',', Lead::STATUSES), 'applied_at' => 'nullable|date|before_or_equal:today', 'notes' => 'nullable|string|max:5000',
        ]);
        (new LeadImport($this->candidate($request)))->save($data, 'manual');
        return back()->with('toast', ['type' => 'store', 'message' => 'Saved to your job leads.']);
    }

    // Any status can be set by hand, also back to an earlier one.
    public function update(Request $request, Lead $lead)
    {
        $this->own($request, $lead);
        $data = $request->validate([
            'status' => 'sometimes|in:'.implode(',', Lead::STATUSES),
            'applied_at' => 'sometimes|nullable|date|before_or_equal:today',
            'notes' => 'sometimes|nullable|string|max:10000',
        ]);
        $lead->fill($data);
        if (in_array($lead->status, ['applied', 'interviewing', 'offer'], true) && ! array_key_exists('applied_at', $data)) { $lead->applied_at ??= today(); }
        $lead->save();
        return back()->with('toast', ['type' => 'update', 'message' => 'Lead updated.']);
    }

    // Opening a posting from the list marks the lead opened.
    public function open(Request $request, Lead $lead)
    {
        $this->own($request, $lead);
        $lead->advance('opened')->save();
        $url = $lead->url ?: $lead->opportunity?->application_url ?: ($lead->opportunity ? $lead->opportunity->publicUrl() : null);
        abort_unless($url && preg_match('~^https?://~i', $url), 404);
        return redirect()->away($url);
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120|mimes:json,csv,txt']);
        $file = $request->file('file');
        try {
            $stats = (new LeadImport($this->candidate($request)))->file($file->getRealPath(), $file->getClientOriginalName());
        } catch (Throwable $e) {
            return back()->withErrors(['file' => Str::limit($e->getMessage(), 200)]);
        }
        return back()->with('toast', ['type' => 'store', 'message' => "{$stats['added']} added, {$stats['updated']} merged with existing leads, {$stats['skipped']} rows without company or role, {$stats['contacts']} contacts."]);
    }

    public function storeContact(Request $request, Lead $lead)
    {
        $this->own($request, $lead);
        LeadContact::create([...$this->contactData($request), 'candidate_id' => $lead->candidate_id, 'company_id' => $lead->company_id, 'company_name' => $lead->company_name,
            'source' => $request->input('source') === 'ai' ? 'ai' : 'manual']);
        return back()->with('toast', ['type' => 'store', 'message' => 'Contact saved.']);
    }

    public function updateContact(Request $request, LeadContact $contact)
    {
        abort_unless($contact->candidate_id === $this->candidate($request)->id, 404);
        $data = $request->has('contacted') ? ['contacted_at' => $request->boolean('contacted') ? now() : null] : $this->contactData($request);
        $contact->update($data);
        return back()->with('toast', ['type' => 'update', 'message' => 'Contact updated.']);
    }

    public function destroyContact(Request $request, LeadContact $contact)
    {
        abort_unless($contact->candidate_id === $this->candidate($request)->id, 404);
        $contact->delete();
        return back()->with('toast', ['type' => 'destroy', 'message' => 'Contact removed.']);
    }

    // Asks the AI for one person at the company from public pages; nothing is saved until the owner keeps it.
    public function suggestContact(Request $request, Lead $lead, OpportunityClassifier $classifier)
    {
        $this->own($request, $lead);
        abort_unless($classifier->enabled(), 422, 'AI is off.');
        $prompt = implode("\n", array_filter(['Company: '.$lead->company_name, $lead->company?->website ? 'Website: '.$lead->company->website : null,
            'Role: '.$lead->title, $lead->url ? 'Job posting: '.$lead->url : null]));
        try {
            $answer = (new ContactFinder)->prompt($prompt, provider: $classifier->provider(), model: $classifier->model('discovery'))->toArray();
        } catch (Throwable $e) {
            Log::warning('Contact suggestion failed.', ['lead_id' => $lead->id, 'error' => Str::limit($e->getMessage(), 300)]);
            return response()->json(['message' => 'The search did not work this time. Try again later.'], 502);
        }
        $url = fn ($value) => is_string($value) && preg_match('~^https?://\S+$~i', $value) && strlen($value) <= 500 ? $value : null;
        if (empty($answer['found']) || trim((string) ($answer['name'] ?? '')) === '') {
            return response()->json(['message' => 'No one was found on public pages.']);
        }
        return response()->json(['contact' => [
            'name' => Str::limit(trim($answer['name']), 191, ''), 'title' => Str::limit(trim((string) $answer['title']), 191, ''),
            'profile_url' => $url($answer['profile_url'] ?? null), 'source_url' => $url($answer['source_url'] ?? null), 'note' => Str::limit(trim((string) ($answer['why'] ?? '')), 500, ''),
        ]]);
    }

    private function contactData(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:191', 'title' => 'nullable|string|max:191', 'profile_url' => 'nullable|url|max:500',
            'source_url' => 'nullable|url|max:500', 'note' => 'nullable|string|max:2000', 'mutual' => 'nullable|string|max:191',
        ]);
    }

    private function card(Lead $lead, Collection $contacts): array
    {
        $date = $lead->posted_at?->format('j M');
        return [
            ...$lead->only('id', 'company_name', 'title', 'url', 'status', 'eligibility', 'eligibility_quote', 'score', 'origin', 'external_ref', 'notes'),
            'urls' => array_values(array_diff($lead->urls ?? [], [$lead->url])),
            'reasons' => $lead->reasons ?? [],
            'extra' => $lead->extra ?? [],
            'applied_at' => $lead->applied_at?->toDateString(),
            'opened_at' => $lead->opened_at?->toIso8601String(),
            'posted' => $lead->reposted_at ? 'Repost, first posted '.$lead->posted_at?->format('j M Y')
                : ($date ? 'Posted '.$date.(isset(self::DATE_SOURCES[$lead->date_source]) ? ' ('.self::DATE_SOURCES[$lead->date_source].')' : '') : null),
            'company' => $lead->company ? ['verified' => (bool) $lead->company->verified_at, 'stage' => $lead->company->stage, 'logo' => $lead->company->logo,
                'url' => $lead->company->hasPublicName() ? route('companies.show', $lead->company_id, false) : null] : null,
            'open_url' => route('leads.open', $lead),
            'contacts' => ($contacts[$lead->company_key] ?? collect())->map(fn ($contact) => [
                ...$contact->only('id', 'name', 'title', 'profile_url', 'source_url', 'note', 'mutual', 'source'),
                'contacted_at' => $contact->contacted_at?->toDateString(),
            ])->values(),
        ];
    }

    private function skip(LeadSkip $skip): array
    {
        return [...$skip->only('id', 'company_name', 'title', 'url', 'reason', 'detail'), 'run_date' => $skip->run_date->toDateString()];
    }

    private function candidate(Request $request): Candidate
    {
        return $request->user()->ensureCandidateProfile() ?? abort(403);
    }

    private function own(Request $request, Lead $lead): void
    {
        abort_unless($lead->candidate_id === $this->candidate($request)->id, 404);
    }
}
