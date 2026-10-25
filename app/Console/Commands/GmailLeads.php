<?php

namespace App\Console\Commands;

use App\Leads\GmailImport;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

class GmailLeads extends Command
{
    protected $signature = 'leads:gmail {--days=3 : Emails from the last N days} {--user= : Only this admin, by id or email}';
    protected $description = 'Add job applications from the confirmation emails in each connected Gmail (read-only)';

    public function handle(GmailImport $gmail): int
    {
        $users = User::where('is_admin', true)->whereNotNull('gmail_token')
            ->when($this->option('user'), fn ($query, $user) => $query->where(is_numeric($user) ? 'id' : 'email', $user))->get();
        foreach ($users as $user) {
            try {
                $stats = $gmail->sync($user, (int) $this->option('days'));
                $this->info("{$user->email}: {$stats['emails']} emails, {$stats['added']} added, {$stats['updated']} merged, {$stats['left']} not about an application.");
            } catch (Throwable $e) {
                $this->error("{$user->email}: {$e->getMessage()}");
            }
        }
        return self::SUCCESS;
    }
}
