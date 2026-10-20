<?php

namespace App\Http\Controllers;

use App\Models\SavedSearch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class SavedSearchController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isCandidate(), 403);
        $initial = $this->filters($request->query(), true);
        return Inertia::render('SavedSearches/Index', [
            'searches' => SavedSearch::where('user_id', $request->user()->id)->latest()->get()->map(fn ($search) => [
                'id' => $search->id,
                'name' => $search->name,
                'filters' => $search->filters,
                'frequency' => $search->frequency,
                'timezone' => $search->timezone,
                'last_sent_at' => $search->last_sent_at?->toIso8601String(),
                'browse_url' => self::browseUrl($search->filters, true),
            ]),
            'initialFilters' => $initial,
            'initialBrowseUrl' => self::browseUrl($initial, true),
            'deliveryEnabled' => (bool) config('importer.digests_enabled'),
            'timezones' => \DateTimeZone::listIdentifiers(),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->isCandidate(), 403);
        abort_if(SavedSearch::where('user_id', $request->user()->id)->count() >= 20, 422, 'You can save up to 20 searches.');
        $data = $this->validated($request);
        SavedSearch::create([...$data, 'user_id' => $request->user()->id]);
        return redirect()->route('saved-searches.index')->with('toast', ['type' => 'store', 'message' => 'Search saved.']);
    }

    public function update(Request $request, SavedSearch $savedSearch)
    {
        $this->authorizeSearch($request, $savedSearch);
        $data = $this->validated($request);
        if ($data['frequency'] !== 'off' && $savedSearch->frequency !== 'off') {
            unset($data['subscribed_at']);
        }
        $savedSearch->update($data);
        return back()->with('toast', ['type' => 'update', 'message' => 'Saved search updated.']);
    }

    public function destroy(Request $request, SavedSearch $savedSearch)
    {
        $this->authorizeSearch($request, $savedSearch);
        $savedSearch->delete();
        return back()->with('toast', ['type' => 'destroy', 'message' => 'Saved search removed.']);
    }

    public function unsubscribeConfirmation(SavedSearch $savedSearch)
    {
        return response()->view('saved-searches.unsubscribe', ['search' => $savedSearch, 'done' => false])
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'private, no-store');
    }

    public function unsubscribe(SavedSearch $savedSearch)
    {
        $savedSearch->update(['frequency' => 'off', 'subscribed_at' => null]);
        return response()->view('saved-searches.unsubscribe', ['search' => $savedSearch, 'done' => true])
            ->header('X-Robots-Tag', 'noindex, nofollow')->header('Cache-Control', 'private, no-store');
    }

    private function authorizeSearch(Request $request, SavedSearch $savedSearch): void
    {
        abort_unless($request->user()->isCandidate() && (int) $savedSearch->user_id === (int) $request->user()->id, 403);
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'filters' => 'present|array',
            'frequency' => 'required|in:off,daily,weekly',
            'timezone' => ['required', Rule::in(\DateTimeZone::listIdentifiers())],
            'consent' => $request->input('frequency') === 'off' ? ['exclude'] : ['required', 'accepted'],
        ]);
        return [
            'name' => $data['name'],
            'filters' => $this->filters($data['filters']),
            'frequency' => $data['frequency'],
            'timezone' => $data['timezone'],
            'subscribed_at' => $data['frequency'] === 'off' ? null : now(),
        ];
    }

    private function filters(array $input, bool $query = false): array
    {
        foreach (['country', 'currency'] as $field) {
            if (isset($input[$field]) && is_string($input[$field])) {
                $input[$field] = strtoupper(trim($input[$field]));
            }
        }
        foreach (['fields', 'skills', 'companies', 'cities'] as $field) {
            if (isset($input[$field]) && is_array($input[$field])) {
                $input[$field] = array_values(array_filter($input[$field], fn ($value) => $value !== '' && $value !== null));
            }
        }
        $validator = Validator::make($input, [
            'search' => 'nullable|string|max:150',
            'category' => 'nullable|in:internships,jobs,tunisia,remote-from-tunisia,relocation',
            'kind' => 'nullable|in:internship,job',
            'country' => 'nullable|string|regex:/^[A-Z]{2}$/',
            'work_type' => 'nullable|in:onsite,hybrid,remote',
            'seniority' => 'nullable|in:entry,graduate,mid,senior,lead,executive',
            'employment_type' => 'nullable|in:FULL_TIME,PART_TIME,CONTRACTOR,TEMPORARY,INTERN,VOLUNTEER,OTHER',
            'language' => 'nullable|string|regex:/^[a-z]{2}(-[A-Z]{2})?$/',
            'program_type' => 'nullable|in:pfe,summer,apprenticeship,graduate,other',
            'compensation_status' => 'nullable|in:paid,unpaid,unspecified',
            'fields' => 'nullable|array|max:20', 'fields.*' => 'integer|min:1',
            'skills' => 'nullable|array|max:20', 'skills.*' => 'integer|min:1',
            'companies' => 'nullable|array|max:20', 'companies.*' => 'integer|min:1',
            'cities' => 'nullable|array|max:20', 'cities.*' => 'integer|min:1',
            'field' => 'nullable|integer|min:1',
            'posted_within' => 'nullable|integer|in:1,3,7,14,30,90',
            'min_pay' => 'nullable|numeric|min:0|max:9999999999.99',
            'currency' => 'nullable|required_with:min_pay|string|regex:/^[A-Z]{3}$/',
            'pay_unit' => 'nullable|required_with:min_pay|in:hour,day,week,month,year',
            'duration_max' => 'nullable|integer|min:1|max:104',
            'start_after' => 'nullable|date_format:Y-m-d',
            'audience' => 'nullable|in:students,professionals,both',
            'worldwide' => 'nullable|boolean',
            'remote_from_tunisia' => 'nullable|boolean',
            'include_unknown_remote' => 'nullable|boolean',
            'relocation' => 'nullable|boolean',
        ]);
        abort_if($query && $validator->fails(), 404);
        $data = $validator->validate();
        return array_filter($data, fn ($value) => $value !== null && $value !== '' && $value !== []);
    }

    // Emails link to the public list; the saved searches page opens it inside /app.
    public static function browseUrl(array $filters, bool $app = false): string
    {
        $category = $filters['category'] ?? null;
        unset($filters['category']);
        $query = http_build_query($filters);
        return ($app ? '/app' : rtrim(config('app.url'), '/')).'/opportunities'.($category ? '/'.$category : '').($query ? '?'.$query : '');
    }
}
