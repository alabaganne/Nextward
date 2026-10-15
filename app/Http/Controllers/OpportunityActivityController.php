<?php

namespace App\Http\Controllers;

use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class OpportunityActivityController extends Controller
{
    private const LIMIT = 500;

    public function opened(Request $request, Internship $internship)
    {
        abort_unless($request->user()->isCandidate(), 403);

        DB::table('opportunity_views')->upsert([[
            'student_id' => $request->user()->userable_id,
            'internship_id' => $internship->id,
            'viewed_at' => now(),
        ]], ['student_id', 'internship_id'], ['viewed_at']);

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
        $studentId = $request->user()->userable_id;

        $views = collect($data['views'] ?? [])
            ->filter(fn ($date, $id) => ctype_digit((string) $id))
            ->map(fn ($date) => Carbon::parse($date)->timezone(config('app.timezone'))->min(now()));
        $known = Internship::whereIn('id', $views->keys())->pluck('id');
        $current = DB::table('opportunity_views')->where('student_id', $studentId)
            ->whereIn('internship_id', $known)->pluck('viewed_at', 'internship_id');
        $rows = $known->map(fn ($id) => [
            'student_id' => $studentId,
            'internship_id' => $id,
            'viewed_at' => isset($current[$id]) ? $views[$id]->max(Carbon::parse($current[$id])) : $views[$id],
        ])->all();
        if ($rows) {
            DB::table('opportunity_views')->upsert($rows, ['student_id', 'internship_id'], ['viewed_at']);
        }

        $saves = Internship::whereIn('id', $data['saves'] ?? [])
            ->where('publication_status', 'published')->whereNull('merged_into_id')->pluck('id');
        $added = $saves->isEmpty() ? 0 : DB::table('likes')->insertOrIgnore(
            $saves->map(fn ($id) => ['student_id' => $studentId, 'internship_id' => $id])->all()
        );

        return response()->json([
            'views' => DB::table('opportunity_views')->where('student_id', $studentId)
                ->orderByDesc('viewed_at')->limit(self::LIMIT)->pluck('viewed_at', 'internship_id')
                ->map(fn ($date) => Carbon::parse($date)->toIso8601String()),
            'saves' => DB::table('likes')->where('student_id', $studentId)->pluck('internship_id'),
            'saves_added' => $added,
        ]);
    }
}
