<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Opportunity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpportunityActivityController extends Controller
{
    private const LIMIT = 500;

    public function opened(Request $request, Opportunity $opportunity)
    {
        abort_unless($request->user()->isCandidate(), 403);

        DB::table('opportunity_views')->upsert([[
            'candidate_id' => $request->user()->userable_id,
            'opportunity_id' => $opportunity->id,
            'viewed_at' => now(),
        ]], ['candidate_id', 'opportunity_id'], ['viewed_at']);
        if (Lead::enabledFor($request->user())) {
            rescue(fn () => Lead::where('candidate_id', $request->user()->userable_id)->where('opportunity_id', $opportunity->id)->get()->each(fn ($lead) => $lead->advance('opened')->save()));
        }

        return response()->noContent();
    }

    public function sync(Request $request)
    {
        abort_unless($request->user()->isCandidate(), 403);
        $data = $request->validate([
            'views' => ['nullable', 'array', 'max:'.self::LIMIT],
            'views.*' => ['date'],
            'saves' => ['nullable', 'array', 'max:'.self::LIMIT],
            'saves.*' => ['integer'],
        ]);
        $candidateId = $request->user()->userable_id;

        $views = collect($data['views'] ?? [])
            ->filter(fn ($date, $id) => ctype_digit((string) $id))
            ->map(fn ($date) => Carbon::parse($date)->timezone(config('app.timezone'))->min(now()));
        $known = Opportunity::whereIn('id', $views->keys())->pluck('id');
        $current = DB::table('opportunity_views')->where('candidate_id', $candidateId)
            ->whereIn('opportunity_id', $known)->pluck('viewed_at', 'opportunity_id');
        $rows = $known->map(fn ($id) => [
            'candidate_id' => $candidateId,
            'opportunity_id' => $id,
            'viewed_at' => isset($current[$id]) ? $views[$id]->max(Carbon::parse($current[$id])) : $views[$id],
        ])->all();
        if ($rows) {
            DB::table('opportunity_views')->upsert($rows, ['candidate_id', 'opportunity_id'], ['viewed_at']);
        }

        $saves = Opportunity::whereIn('id', $data['saves'] ?? [])
            ->where('publication_status', 'published')->whereNull('merged_into_id')->pluck('id');
        $added = $saves->isEmpty() ? 0 : DB::table('likes')->insertOrIgnore(
            $saves->map(fn ($id) => ['candidate_id' => $candidateId, 'opportunity_id' => $id])->all()
        );

        return response()->json([
            'views' => DB::table('opportunity_views')->where('candidate_id', $candidateId)
                ->orderByDesc('viewed_at')->limit(self::LIMIT)->pluck('viewed_at', 'opportunity_id')
                ->map(fn ($date) => Carbon::parse($date)->toIso8601String()),
            'saves' => DB::table('likes')->where('candidate_id', $candidateId)->pluck('opportunity_id'),
            'saves_added' => $added,
        ]);
    }
}
