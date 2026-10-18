<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;

class SignupSourceController extends Controller
{
    public function __invoke()
    {
        // Users have no created_at; terms are accepted at sign-up.
        $rows = User::query()
            ->whereIn('userable_type', ['candidate', 'company'])
            ->selectRaw('signup_ref as ref, count(*) as total')
            ->selectRaw('sum(case when terms_accepted_at >= ? then 1 else 0 end) as last_7', [now()->subDays(7)])
            ->selectRaw('sum(case when terms_accepted_at >= ? then 1 else 0 end) as last_30', [now()->subDays(30)])
            ->selectRaw("sum(case when userable_type = 'candidate' then 1 else 0 end) as candidates")
            ->selectRaw("sum(case when userable_type = 'candidate' and exists (select 1 from saved_searches where saved_searches.user_id = users.id and saved_searches.frequency <> 'off') then 1 else 0 end) as with_alerts")
            ->groupBy('signup_ref')
            ->get()
            ->map(fn ($row) => [
                'ref' => $row->ref,
                'last_7' => (int) $row->last_7,
                'last_30' => (int) $row->last_30,
                'total' => (int) $row->total,
                'candidates' => (int) $row->candidates,
                'with_alerts' => (int) $row->with_alerts,
            ])
            ->sortBy([fn ($a, $b) => is_null($a['ref']) <=> is_null($b['ref']), ['total', 'desc'], ['ref', 'asc']])
            ->values();

        return Inertia::render('Signups/Index', ['rows' => $rows]);
    }
}
