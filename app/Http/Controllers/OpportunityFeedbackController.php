<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OpportunityFeedbackController extends Controller
{
    public function report(Request $request, Internship $internship)
    {
        abort_unless($internship->publication_status === 'published' && ! $internship->merged_into_id, 404);
        $data = $request->validate([
            'reason' => 'required|in:closed,broken_link,incorrect,scam,duplicate,other',
            'details' => 'nullable|string|max:2000',
        ]);
        DB::table('opportunity_reports')->insert([
            ...$data, 'internship_id' => $internship->id, 'user_id' => $request->user()?->id,
            'status' => 'open', 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('toast', ['type' => 'store', 'message' => 'Thanks. We will review this opportunity.']);
    }

    public function claim(Request $request, Company $company)
    {
        abort_unless($request->user()?->isCompany(), 403);
        abort_if($company->user || $company->claimed_by_company_id || $company->id === $request->user()->userable_id, 422, 'This company cannot be claimed.');
        $data = $request->validate(['evidence' => 'required|string|min:30|max:3000']);
        DB::table('company_claims')->updateOrInsert(['company_id' => $company->id, 'user_id' => $request->user()->id], [
            'evidence' => $data['evidence'], 'status' => 'pending', 'review_notes' => null, 'created_at' => now(), 'updated_at' => now(),
        ]);
        return back()->with('toast', ['type' => 'store', 'message' => 'Company claim submitted for ownership review.']);
    }
}
