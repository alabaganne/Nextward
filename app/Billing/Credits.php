<?php

namespace App\Billing;

use App\Models\ApplicationKit;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Credits pay for tailored CVs and cover letters. While billing is off nothing is charged or blocked.
class Credits
{
    public function enabled(): bool
    {
        return (bool) config('billing.enabled');
    }

    public function balance(User $user): int
    {
        return (int) CreditTransaction::where('user_id', $user->id)->sum('amount');
    }

    // How many drafts one credit covers: the first one plus the rewrites.
    public function draftsPerCredit(): int
    {
        return 1 + max(0, (int) config('billing.rewrites_per_kit'));
    }

    public function needsCredit(ApplicationKit $kit): bool
    {
        return $kit->generations >= $kit->credits_used * $this->draftsPerCredit();
    }

    public function canGenerate(User $user, ApplicationKit $kit): bool
    {
        return ! $this->enabled() || $user->isAdmin() || ! $this->needsCredit($kit) || $this->balance($user) > 0;
    }

    // Takes a credit when this draft is not covered yet. Returns false when the user has none left.
    public function chargeFor(User $user, ApplicationKit $kit): bool
    {
        if (! $this->enabled() || $user->isAdmin() || ! $this->needsCredit($kit)) {
            return true;
        }
        return DB::transaction(function () use ($user, $kit) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if ($this->balance($user) < 1) {
                return false;
            }
            CreditTransaction::create(['user_id' => $user->id, 'amount' => -1, 'reason' => 'kit', 'application_kit_id' => $kit->id]);
            $kit->increment('credits_used');
            return true;
        });
    }

    // Gives back the credit taken for a draft that failed.
    public function refund(User $user, ApplicationKit $kit): void
    {
        $taken = CreditTransaction::where('user_id', $user->id)->where('application_kit_id', $kit->id)->sum('amount');
        if ($taken < 0 && $kit->credits_used > 0 && $kit->generations <= ($kit->credits_used - 1) * $this->draftsPerCredit()) {
            CreditTransaction::create(['user_id' => $user->id, 'amount' => 1, 'reason' => 'refund', 'application_kit_id' => $kit->id]);
            $kit->decrement('credits_used');
        }
    }

    public function grant(User $user, int $amount, string $reason = 'grant', ?string $note = null): void
    {
        CreditTransaction::create(['user_id' => $user->id, 'amount' => $amount, 'reason' => $reason, 'note' => $note]);
    }

    // Adds the credits of a paid payment once, however many times the gateway calls back.
    public function addPurchase(Payment $payment): bool
    {
        return DB::transaction(function () use ($payment) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($payment->status === 'paid') {
                return false;
            }
            $payment->update(['status' => 'paid', 'paid_at' => now()]);
            CreditTransaction::create(['user_id' => $payment->user_id, 'amount' => $payment->credits, 'reason' => 'purchase', 'payment_id' => $payment->id]);
            return true;
        });
    }
}
