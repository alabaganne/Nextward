<?php

namespace App\Http\Controllers;

use App\Http\Resources\OpportunityResource;
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
        $locale = $request->is('fr/*') ? 'fr' : 'en';
        if ($model->claimed_by_company_id) {
            return redirect(Company::with('user')->findOrFail($model->claimed_by_company_id)->publicUrl($locale), 301);
        }
        abort_unless($model->hasPublicName(), 404);
        if ($companySlug !== basename(parse_url($model->publicUrl($locale), PHP_URL_PATH))) {
            return redirect($model->publicUrl($locale), 301);
        }
        $opportunities = $model->opportunities()->available()
            ->with(['company.user', 'company.city', 'field', 'city', 'skills', 'sources.importSource', 'translations'])
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
            'opportunities' => OpportunityResource::collection($opportunities),
            'seo' => PublicSeo::company($model, $opportunities->count()),
            'translation' => parse_url($model->publicUrl($locale === 'fr' ? 'en' : 'fr'), PHP_URL_PATH),
        ]);
    }
}
