<?php

namespace App\Console\Commands;

use App\Models\CreditTransaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MonthlyCredits extends Command
{
    protected $signature = 'credits:monthly';
    protected $description = 'Give every candidate with a verified email their free credits for this month (once per month)';

    public function handle(): int
    {
        $amount = (int) config('billing.monthly_credits');
        if (! config('billing.enabled') || $amount < 1) {
            $this->info('Monthly credits are off.');
            return self::SUCCESS;
        }

        $note = 'monthly:'.now()->format('Y-m');
        $given = 0;
        User::where('userable_type', 'candidate')->whereNotNull('email_verified_at')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))->from('credit_transactions')
                ->whereColumn('credit_transactions.user_id', 'users.id')->where('reason', 'monthly')->where('note', $note))
            ->select('id')->chunkById(500, function ($users) use ($amount, $note, &$given) {
                CreditTransaction::insert($users->map(fn ($user) => ['user_id' => $user->id, 'amount' => $amount, 'reason' => 'monthly', 'note' => $note, 'created_at' => now()])->all());
                $given += $users->count();
            });

        $this->info("Gave {$amount} credits to {$given} candidate(s).");
        return self::SUCCESS;
    }
}
