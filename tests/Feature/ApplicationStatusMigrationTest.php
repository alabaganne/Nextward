<?php

namespace Tests\Feature;

use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ApplicationStatusMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
    }

    public function test_legacy_application_decisions_survive_the_dashboard_schema_upgrade(): void
    {
        $applications = Application::orderBy('id')->take(3)->get();
        foreach ($applications as $application) {
            $application->update(['attachments' => [['name' => 'cv.pdf', 'url' => '/storage/applications/cv.pdf']]]);
        }
        $migration = require database_path('migrations/2026_07_05_000000_add_design_fields_to_internships_and_applications.php');
        $migration->down();

        foreach ([true, false, null] as $index => $legacyStatus) {
            DB::table('applications')->where('id', $applications[$index]->id)
                ->update(['status' => $legacyStatus]);
        }

        $migration->up();

        foreach (['offer', 'closed', 'submitted'] as $index => $status) {
            $application = $applications[$index];
            $this->assertDatabaseHas('applications', [
                'id' => $application->id,
                'student_id' => $application->student_id,
                'internship_id' => $application->internship_id,
                'cover_letter' => $application->cover_letter,
                'message' => $application->message,
                'attachments' => $application->getRawOriginal('attachments'),
                'status' => $status,
            ]);
        }
    }

    public function test_schema_rollback_restores_legacy_decisions_and_retains_application_records(): void
    {
        $applications = Application::orderBy('id')->take(3)->get();
        foreach (['offer', 'closed', 'interview'] as $index => $status) {
            $applications[$index]->update([
                'status' => $status,
                'attachments' => [['name' => 'cv.pdf', 'url' => '/storage/applications/cv.pdf']],
            ]);
        }

        $migration = require database_path('migrations/2026_07_05_000000_add_design_fields_to_internships_and_applications.php');
        $migration->down();

        foreach ([true, false, null] as $index => $status) {
            $application = $applications[$index];
            $this->assertDatabaseHas('applications', [
                'id' => $application->id,
                'student_id' => $application->student_id,
                'company_id' => $application->company_id,
                'internship_id' => $application->internship_id,
                'cover_letter' => $application->cover_letter,
                'message' => $application->message,
                'attachments' => $application->getRawOriginal('attachments'),
                'status' => $status,
            ]);
        }

        $migration->up();
    }
}
