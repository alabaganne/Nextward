<?php

namespace App\Console\Commands;

use App\Ai\Agents\SearchProfileReader;
use App\Ai\OpportunityClassifier;
use App\Importing\CandidateProfiles;
use App\Importing\OpportunityImporter;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laravel\Ai\Files\Document;

class ReadSearchProfile extends Command
{
    protected $signature = 'opportunities:profile {resume? : Path to a resume PDF} {--name= : Name to save it under (default: the file name)}
        {--wants= : What the person also looks for, such as "AI and data science roles"} {--forget= : Remove a saved profile} {--list : Show every profile imports use}
        {--countries= : Preferred countries for on-site roles, as codes like US,DE,FR} {--needs-visa : Lives in the profile country and needs a visa to work abroad}';
    protected $description = 'Read a resume with AI and save the roles it fits, so imports find listings for it';

    public function handle(OpportunityClassifier $ai, OpportunityImporter $importer): int
    {
        if ($name = $this->option('forget')) {
            CandidateProfiles::forget($name) ? $this->info("Removed {$name}.") : $this->error("No saved profile is called {$name}.");
            return self::SUCCESS;
        }
        $path = $this->argument('resume');
        $countries = array_values(array_filter(array_map(fn ($code) => strtoupper(trim($code)), explode(',', (string) $this->option('countries')))));
        // Updates the place rules of a saved profile without reading the resume again.
        if (! $path && $this->option('name') && ($countries || $this->option('needs-visa'))) {
            $saved = CandidateProfiles::saved()[$this->option('name')] ?? null;
            if (! $saved) { $this->error('No saved profile is called '.$this->option('name').'.'); return self::FAILURE; }
            $profile = CandidateProfiles::save($saved['name'], [...$saved, 'countries' => $countries ?: $saved['countries'], 'needs_visa' => $this->option('needs-visa') || $saved['needs_visa']]);
            $this->info('Saved '.$profile['name'].': countries '.(implode(', ', $profile['countries']) ?: 'any').($profile['needs_visa'] ? ', needs a visa abroad.' : '.'));
            return self::SUCCESS;
        }
        if ($this->option('list') || ! $path) {
            $rows = collect(CandidateProfiles::load()->profiles)->map(fn ($p) => [$p['name'], implode(', ', $p['fields']), Str::limit(implode(', ', $p['skills']), 60), implode(', ', $p['kinds']) ?: 'any', implode(', ', $p['seniority']) ?: 'any']);
            $rows->isEmpty() ? $this->info('No profiles yet.') : $this->table(['Name', 'Fields', 'Skills', 'Kinds', 'Levels'], $rows);
            return self::SUCCESS;
        }
        if (! is_file($path) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf') { $this->error('Give the path to a PDF resume.'); return self::FAILURE; }
        if (! $ai->enabled()) { $this->error('AI is off. Set '.$ai->keyName().' to read a resume.'); return self::FAILURE; }

        $fields = array_values(array_diff($importer->fieldNames(), ['Other']));
        $skills = $importer->skillNames();
        $wants = trim((string) $this->option('wants'));
        $response = (new SearchProfileReader($fields, $skills))->prompt(
            'Read the attached resume.'.($wants !== '' ? " The person also wants: {$wants}" : ''),
            attachments: [Document::fromPath($path)],
            provider: $ai->provider(),
            model: $ai->model('review'),
        );
        $data = $response->toArray();
        $known = fn (array $names, array $list) => array_values(array_intersect($list, $names));
        $profile = CandidateProfiles::save($this->option('name') ?: Str::of(basename($path, '.pdf'))->replace(['_', '-'], ' ')->squish()->toString(), [
            'fields' => $known($data['fields'] ?? [], $fields),
            'skills' => $known($data['skills'] ?? [], $skills),
            'kinds' => $data['kinds'] ?? [],
            'seniority' => array_values(array_intersect($data['seniority'] ?? [], ['entry', 'graduate', 'mid', 'senior', 'lead', 'executive'])),
            'country' => $data['country_code'] ?? null,
            'countries' => $countries,
            'needs_visa' => (bool) $this->option('needs-visa'),
        ]);

        $this->info('Saved '.$profile['name'].'. '.trim((string) ($data['summary'] ?? '')));
        $this->line('Fields: '.(implode(', ', $profile['fields']) ?: 'none'));
        $this->line('Skills: '.(implode(', ', $profile['skills']) ?: 'none'));
        $this->line('Kinds: '.(implode(', ', $profile['kinds']) ?: 'any').'. Levels: '.(implode(', ', $profile['seniority']) ?: 'any').'.');
        $this->line('Run php artisan opportunities:fetch to add recent listings for it.');
        return self::SUCCESS;
    }
}
