<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApplicationResource;
use Illuminate\Support\Facades\Gate;
use App\Models\Application;
use App\Models\Internship;
use Inertia\Inertia;

class InternshipApplicationController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Internship $internship)
    {
        Gate::authorize('update', $internship);

        return Inertia::render('Internships/Applications', [
            'applications' => ApplicationResource::collection(
                Application::where('internship_id', $internship->id)
                    ->where('channel', 'internal')
                    ->latest()
                    ->paginate(8)
            ),
            'internship' => $internship
        ]);
    }
}
