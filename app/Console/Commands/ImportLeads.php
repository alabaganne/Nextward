<?php

namespace App\Console\Commands;

use App\Leads\LeadImport;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Console\Command;

class ImportLeads extends Command
{
    protected $signature = 'leads:import {file : jobs.json, a Tsenta or LinkedIn CSV export, or any CSV or JSON with company and role columns}
        {--user= : The admin to import for, by id or email (the first admin by default)}
        {--origin= : tracker, tsenta, linkedin, csv or json, when the file name does not say}';
    protected $description = 'Add applications and saved roles from another tracker to job leads, merged with the leads for the same jobs';

    public function handle(): int
    {
        $user = User::where('is_admin', true)->when($this->option('user'), fn ($query, $user) => $query->where(is_numeric($user) ? 'id' : 'email', $user))->orderBy('id')->first();
        if (! Lead::enabledFor($user)) {
            $this->error('No admin with a candidate profile found.');
            return self::FAILURE;
        }
        $file = $this->argument('file');
        if (! is_readable($file)) {
            $this->error("Cannot read {$file}.");
            return self::FAILURE;
        }
        $stats = (new LeadImport($user->userable))->file($file, basename($file), $this->option('origin'));
        $this->info("{$stats['added']} added, {$stats['updated']} merged with existing leads, {$stats['skipped']} rows without company or role, {$stats['contacts']} contacts.");
        return self::SUCCESS;
    }
}
