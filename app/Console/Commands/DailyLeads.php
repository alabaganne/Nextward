<?php

namespace App\Console\Commands;

use App\Leads\LeadFinder;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadsDigest;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class DailyLeads extends Command
{
    protected $signature = 'leads:daily {--user= : Only this admin, by id or email} {--date= : Run date, today in Tunis by default}';
    protected $description = 'Pick the day\'s job leads for each admin: new roles only, never one seen before or already applied to';

    public function handle(LeadFinder $finder): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : today(config('importer.timezone', 'Africa/Tunis'));
        $users = User::where('is_admin', true)->when($this->option('user'), fn ($query, $user) => $query->where(is_numeric($user) ? 'id' : 'email', $user))->get()
            ->filter(fn (User $user) => Lead::enabledFor($user));
        foreach ($users as $user) {
            $stats = $finder->run($user->userable, $date);
            $this->info("{$user->email}: {$stats['added']} new leads, {$stats['skipped']} left out.");
            $leads = Lead::where('candidate_id', $user->userable_id)->whereDate('digest_date', $date)->orderByDesc('score')->get();
            if (config('leads.digest_email') && $stats['added'] && $leads->isNotEmpty()) { $user->notify(new LeadsDigest($leads)); }
        }
        return self::SUCCESS;
    }
}
