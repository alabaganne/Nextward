<?php

namespace App\Http\Controllers;

use App\Http\Resources\InternshipResource;
use App\Models\Company;
use App\Support\PublicSeo;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PublicCompanyController extends Controller
{
    public function show(Request $request, string $companySlug)
    {
        abort_unless(preg_match('/^([1-9][0-9]*)-/', $companySlug, $matches), 404);
        $model = Company::with('user', 'city')->findOrFail($matches[1]);
        if ($model->claimed_by_company_id) {
            return redirect(Company::with('user')->findOrFail($model->claimed_by_company_id)->public_url, 301);
        }
        abort_unless($model->hasPublicName(), 404);
        if ($companySlug !== basename(parse_url($model->public_url, PHP_URL_PATH))) {
            return redirect($model->public_url, 301);
        }
        $opportunities = $model->internships()->available()
            ->with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource'])
            ->withCandidateState($request->user())
            ->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')->limit(50)->get();

        return Inertia::render('Companies/Public', [
            'company' => [
                'id' => $model->id,
                'name' => $model->display_name,
                'logo' => $model->logo,
                'about' => $model->about,
                'website' => $model->website,
                'location' => $model->city?->name ?: $model->location,
                'managed' => (bool) $model->user,
            ],
            'opportunities' => InternshipResource::collection($opportunities),
            'seo' => PublicSeo::company($model, $opportunities->count()),
        ]);
    }
}
