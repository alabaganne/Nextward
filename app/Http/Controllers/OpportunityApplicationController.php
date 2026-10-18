<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use Illuminate\Support\Facades\Gate;
use App\Models\Application;
use App\Models\Opportunity;
use Inertia\Inertia;

class OpportunityApplicationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Opportunity $opportunity)
    {
        Gate::authorize('update', $opportunity);

        return Inertia::render('Opportunities/Applications', [
            'applications' => ApplicationResource::collection(
                Application::where('opportunity_id', $opportunity->id)
                    ->where('channel', 'internal')
                    ->latest()
                    ->paginate(8)
            ),
            'opportunity' => $opportunity
        ]);
    }
}
