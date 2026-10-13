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

        // Test the July upgrade against its historical schema, without later dependent indexes.
        foreach ([
            '2026_10_13_000300_create_opportunity_imports_and_alerts.php',
            '2026_10_13_000200_extend_candidate_profiles_and_application_tracking.php',
            '2026_10_13_000100_expand_opportunity_catalogue.php',
        ] as $filename) {
            (require database_path('migrations/'.$filename))->down();
        }

        // Insert only historical fields; current demo data requires the opportunity schema.
        $cityId = DB::table('cities')->insertGetId(['name' => 'Tunis']);
        $fieldId = DB::table('fields')->insertGetId(['name' => 'Engineering']);
        $companyId = DB::table('companies')->insertGetId(['city_id' => $cityId]);
        $internshipId = DB::table('internships')->insertGetId([
            'title' => 'Historical internship', 'description' => 'Migration fixture.',
            'company_id' => $companyId, 'field_id' => $fieldId, 'city_id' => $cityId,
            'closing_at' => now()->addMonth()->toDateString(),
        ]);
        foreach (range(1, 3) as $index) {
            $studentId = DB::table('students')->insertGetId(['field_id' => $fieldId, 'city_id' => $cityId]);
            DB::table('applications')->insert([
                'student_id' => $studentId, 'internship_id' => $internshipId, 'company_id' => $companyId,
                'cover_letter' => 'Historical cover letter '.$index, 'message' => 'Historical message '.$index,
                'status' => 'submitted',
            ]);
        }
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
