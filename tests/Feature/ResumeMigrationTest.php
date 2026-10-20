<?php

namespace Tests\Feature;

use App\Models\Resume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ResumeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_profile_resumes_become_main_resumes_and_candidates_are_topped_up(): void
    {
        config(['billing.signup_credits' => 20]);
        $topUp = require database_path('migrations/2026_10_21_100100_top_up_candidate_credits.php');
        $resumes = require database_path('migrations/2026_10_21_100000_create_resumes_table.php');
        $topUp->down();
        $resumes->down();
        $this->assertTrue(Schema::hasColumn('candidates', 'resume_path'));

        $withResume = DB::table('candidates')->insertGetId(['resume_path' => 'resumes/old.pdf', 'resume_name' => 'Old CV.pdf']);
        $noName = DB::table('candidates')->insertGetId(['resume_path' => 'resumes/plain.pdf']);
        $without = DB::table('candidates')->insertGetId([]);
        $rich = DB::table('users')->insertGetId(['name' => 'Rich', 'email' => 'rich@example.com', 'password' => 'x', 'userable_type' => 'candidate', 'userable_id' => $without]);
        $poor = DB::table('users')->insertGetId(['name' => 'Poor', 'email' => 'poor@example.com', 'password' => 'x', 'userable_type' => 'candidate', 'userable_id' => $withResume]);
        DB::table('credit_transactions')->insert([
            ['user_id' => $rich, 'amount' => 30, 'reason' => 'purchase', 'created_at' => now()],
            ['user_id' => $poor, 'amount' => 1, 'reason' => 'welcome', 'created_at' => now()],
        ]);

        $resumes->up();
        $topUp->up();

        $this->assertFalse(Schema::hasColumn('candidates', 'resume_path'));
        $this->assertSame(2, Resume::count());
        $old = Resume::where('candidate_id', $withResume)->sole();
        $this->assertSame(['Old CV', 'Old CV.pdf', 'resumes/old.pdf', 'uploaded', 'ready', true], [$old->name, $old->file_name, $old->path, $old->source, $old->status, $old->is_main]);
        $this->assertSame(['name' => 'Resume', 'file_name' => 'resume.pdf'], Resume::where('candidate_id', $noName)->sole()->only('name', 'file_name'));
        $this->assertSame(0, Resume::where('candidate_id', $without)->count());

        $this->assertSame(30, (int) DB::table('credit_transactions')->where('user_id', $rich)->sum('amount'));
        $this->assertSame(20, (int) DB::table('credit_transactions')->where('user_id', $poor)->sum('amount'));

        // Rolling back puts the main resume back on the candidate.
        $topUp->down();
        $resumes->down();
        $this->assertSame('resumes/old.pdf', DB::table('candidates')->where('id', $withResume)->value('resume_path'));
        $this->assertSame(1, (int) DB::table('credit_transactions')->where('user_id', $poor)->sum('amount'));
    }
}
