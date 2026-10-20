<?php

namespace App\Billing;

use App\Models\ApplicationKit;
use App\Models\CreditTransaction;
use App\Models\Payment;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Credits pay for AI work: kits (CV and letter for one role), resume translations and resume reads. While billing is off nothing is charged or blocked.
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

    // A kit or a translated resume: one credit covers its first draft and a few rewrites.
    public function needsCredit(ApplicationKit|Resume $item): bool
    {
        return $item->generations >= $item->credits_used * $this->draftsPerCredit();
    }

    public function canGenerate(User $user, ApplicationKit|Resume $item): bool
    {
        return $this->isFree($user) || ! $this->needsCredit($item) || $this->balance($user) > 0;
    }

    public function isFree(User $user): bool
    {
        return ! $this->enabled() || $user->isAdmin();
    }

    // Takes a credit when this draft is not covered yet. Returns false when the user has none left.
    public function chargeFor(User $user, ApplicationKit|Resume $item): bool
    {
        if ($this->isFree($user) || ! $this->needsCredit($item)) {
            return true;
        }
        return DB::transaction(function () use ($user, $item) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if ($this->balance($user) < 1) {
                return false;
            }
            CreditTransaction::create(['user_id' => $user->id, 'amount' => -1, ...$this->link($item)]);
            $item->increment('credits_used');
            return true;
        });
    }

    // Gives back the credit taken for a draft that failed.
    public function refund(User $user, ApplicationKit|Resume $item): void
    {
        $link = $this->link($item);
        $column = array_key_last($link);
        $taken = CreditTransaction::where('user_id', $user->id)->where($column, $link[$column])->sum('amount');
        if ($taken < 0 && $item->credits_used > 0 && $item->generations <= ($item->credits_used - 1) * $this->draftsPerCredit()) {
            CreditTransaction::create(['user_id' => $user->id, 'amount' => 1, ...$link, 'reason' => 'refund']);
            $item->decrement('credits_used');
        }
    }

    // One AI call with no rewrites, like reading a resume. Returns false when the user has no credit left,
    // true when nothing was charged, or the charge to pass to refundSpend() if the call fails.
    public function spend(User $user, string $reason, ?Resume $resume = null): CreditTransaction|bool
    {
        if ($this->isFree($user)) {
            return true;
        }
        return DB::transaction(function () use ($user, $reason, $resume) {
            User::whereKey($user->id)->lockForUpdate()->first();
            if ($this->balance($user) < 1) {
                return false;
            }
            return CreditTransaction::create(['user_id' => $user->id, 'amount' => -1, 'reason' => $reason, 'resume_id' => $resume?->id]);
        });
    }

    public function refundSpend(CreditTransaction|bool $charge): void
    {
        if ($charge instanceof CreditTransaction) {
            CreditTransaction::create(['user_id' => $charge->user_id, 'amount' => 1, 'reason' => 'refund', 'resume_id' => $charge->resume_id]);
        }
    }

    private function link(ApplicationKit|Resume $item): array
    {
        return $item instanceof Resume ? ['reason' => 'resume', 'resume_id' => $item->id] : ['reason' => 'kit', 'application_kit_id' => $item->id];
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
