<?php

namespace App\Jobs;

use App\Mail\OpportunityDigest;
use App\Models\Internship;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class SendOpportunityDigests implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;
    public int $uniqueFor = 3600;

    public function __construct(public int $userId)
    {
    }

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    public function middleware(): array
    {
        return [(new WithoutOverlapping('opportunity-digest:'.$this->userId))->releaseAfter(60)->expireAfter(180)];
    }

    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }

    public static function isDue(SavedSearch $search): bool
    {
        if (! in_array($search->frequency, ['daily', 'weekly'], true) || ! $search->subscribed_at) {
            return false;
        }
        $localNow = now($search->timezone);
        if ($localNow->hour < 9) {
            return false;
        }
        if (! $search->last_sent_at) {
            return true;
        }
        $last = $search->last_sent_at->copy()->setTimezone($search->timezone);
        return $search->frequency === 'daily'
            ? $last->toDateString() < $localNow->toDateString()
            : $last->copy()->startOfDay()->addWeek()->lte($localNow->copy()->startOfDay());
    }

    public function handle(): void
    {
        if (! config('importer.digests_enabled')) {
            return;
        }
        $user = User::with('userable')->find($this->userId);
        if (! $user?->isCandidate()) {
            return;
        }
        foreach (SavedSearch::where('user_id', $user->id)->whereIn('frequency', ['daily', 'weekly'])->whereNotNull('subscribed_at')->orderBy('id')->get() as $search) {
            if (! self::isDue($search)) {
                continue;
            }
            $this->sendSearch($user, $search);
        }
    }

    private function sendSearch(User $user, SavedSearch $search): void
    {
        $staleSending = DB::table('opportunity_deliveries')->where('user_id', $user->id)
            ->where('saved_search_id', $search->id)->where('status', 'sending');
        if ((clone $staleSending)->exists()) {
            $staleSending->update(['status' => 'uncertain', 'last_error' => 'Delivery was interrupted after the send attempt started. Check the provider before retrying.', 'updated_at' => now()]);
            Log::warning('Opportunity digest requires delivery review.', ['saved_search_id' => $search->id]);
        }
        if (DB::table('opportunity_deliveries')->where('user_id', $user->id)->where('saved_search_id', $search->id)->where('status', 'uncertain')->exists()) {
            return;
        }
        $pending = DB::table('opportunity_deliveries')->where('user_id', $user->id)
            ->where('saved_search_id', $search->id)->where('status', 'pending')->where('attempts', '<', 5)
            ->orderBy('id')->first();
        $batchId = $pending?->batch_id;
        if (! $batchId) {
            $batchId = (string) Str::uuid();
            $matches = Internship::with('sources.importSource')->available()->searchFilters($search->filters)
                ->whereNotExists(function ($query) use ($user) {
                    $query->selectRaw('1')->from('opportunity_deliveries')
                        ->whereColumn('opportunity_deliveries.internship_id', 'internships.id')->where('opportunity_deliveries.user_id', $user->id);
                })->orderByRaw('COALESCE(posted_at, first_seen_at, created_at) DESC')->orderByDesc('id')
                ->limit(100)->get()->filter(fn ($opportunity) => $this->eligibleForUser($opportunity, $user, $search))->take(20);
            foreach ($matches as $opportunity) {
                DB::table('opportunity_deliveries')->insertOrIgnore([
                    'user_id' => $user->id,
                    'internship_id' => $opportunity->id,
                    'saved_search_id' => $search->id,
                    'batch_id' => $batchId,
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
        $records = DB::table('opportunity_deliveries')->where('batch_id', $batchId)->where('user_id', $user->id)->where('status', 'pending')->get();
        if ($records->isEmpty()) {
            return;
        }
        // Recheck both consent and current availability immediately before building the email.
        $search->refresh();
        if (! $search->subscribed_at || $search->frequency === 'off' || ! config('importer.digests_enabled')) {
            return;
        }
        $opportunities = Internship::with(['company.user', 'city', 'sources.importSource'])->available()->searchFilters($search->filters)
            ->whereIn('id', $records->pluck('internship_id'))->get()
            ->filter(fn ($opportunity) => $this->eligibleForUser($opportunity, $user, $search))->values();
        $cancelled = $records->whereNotIn('internship_id', $opportunities->pluck('id'))->pluck('id');
        DB::table('opportunity_deliveries')->whereIn('id', $cancelled)->update(['status' => 'cancelled', 'updated_at' => now()]);
        if ($opportunities->isEmpty()) {
            return;
        }
        $deliveryIds = $records->whereIn('internship_id', $opportunities->pluck('id'))->pluck('id');
        $mail = new OpportunityDigest($search, $opportunities, $batchId);
        // Render before claiming send intent. Template failures are safe to retry without contacting a provider.
        $mail->render();
        DB::table('opportunity_deliveries')->whereIn('id', $deliveryIds)->where('status', 'pending')->update([
            'status' => 'sending', 'attempted_at' => now(), 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now(),
        ]);
        try {
            Mail::to($user->email)->send($mail);
        } catch (\Throwable $exception) {
            // SMTP/network errors may happen after acceptance. Never automatically duplicate an ambiguous send.
            DB::table('opportunity_deliveries')->whereIn('id', $deliveryIds)->update([
                'status' => 'uncertain', 'last_error' => Str::limit($exception->getMessage(), 1500), 'updated_at' => now(),
            ]);
            Log::warning('Opportunity digest delivery is uncertain; review the provider before retrying.', ['batch_id' => $batchId, 'saved_search_id' => $search->id]);
            throw $exception;
        }
        DB::transaction(function () use ($deliveryIds, $search) {
            DB::table('opportunity_deliveries')->whereIn('id', $deliveryIds)->update([
                'status' => 'sent', 'sent_at' => now(), 'last_error' => null, 'updated_at' => now(),
            ]);
            $search->update(['last_sent_at' => now()]);
        });
    }

    private function eligibleForUser(Internship $opportunity, User $user, SavedSearch $search): bool
    {
        if (! $opportunity->isOpen()) {
            return false;
        }
        if (! $opportunity->hasFreshSource()) {
            return false;
        }
        return $opportunity->isEligibleForCountry(
            $user->userable?->country_code,
            ! empty($search->filters['include_unknown_remote']),
        );
    }
}
