<?php

namespace App\Console\Commands;

use App\Billing\Credits;
use App\Models\User;
use Illuminate\Console\Command;

class GrantCredits extends Command
{
    protected $signature = 'credits:grant {email} {amount : Credits to add, or remove with a negative number} {--note=}';
    protected $description = 'Add or remove credits by hand, for example after a bank transfer';

    public function handle(Credits $credits): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        $amount = (int) $this->argument('amount');
        if (! $user || ! $user->isCandidate() || $amount === 0) {
            $this->error('Give the email of a candidate account and a number other than 0.');
            return self::FAILURE;
        }
        $credits->grant($user, $amount, 'grant', $this->option('note'));
        $this->info("{$user->email} now has {$credits->balance($user)} credit(s).");
        return self::SUCCESS;
    }
}
