<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Internships and students become opportunities and candidates. Renames keep every row, key and index.
return new class extends Migration
{
    private const TABLES = ['internships' => 'opportunities', 'students' => 'candidates', 'internship_translations' => 'opportunity_translations'];

    private const COLUMNS = [
        'internship_id' => ['opportunity_translations', 'applications', 'likes', 'opportunity_sources', 'opportunity_views', 'opportunity_audits', 'opportunity_deliveries', 'opportunity_reports', 'indexing_notifications', 'application_kits'],
        'student_id' => ['applications', 'likes', 'opportunity_views', 'application_kits'],
    ];

    public function up(): void
    {
        foreach (self::TABLES as $from => $to) {
            Schema::rename($from, $to);
        }
        foreach (self::COLUMNS as $from => $tables) {
            $to = $from === 'internship_id' ? 'opportunity_id' : 'candidate_id';
            foreach ($tables as $table) {
                if (Schema::hasColumn($table, $from)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($from, $to));
                }
            }
        }
        DB::table('users')->where('userable_type', 'student')->update(['userable_type' => 'candidate']);
        DB::table('skillables')->where('skillable_type', 'internship')->update(['skillable_type' => 'opportunity']);
        DB::table('skillables')->where('skillable_type', 'student')->update(['skillable_type' => 'candidate']);
    }

    public function down(): void
    {
        DB::table('skillables')->where('skillable_type', 'candidate')->update(['skillable_type' => 'student']);
        DB::table('skillables')->where('skillable_type', 'opportunity')->update(['skillable_type' => 'internship']);
        DB::table('users')->where('userable_type', 'candidate')->update(['userable_type' => 'student']);
        foreach (self::COLUMNS as $from => $tables) {
            $to = $from === 'internship_id' ? 'opportunity_id' : 'candidate_id';
            foreach ($tables as $table) {
                if (Schema::hasColumn($table, $to)) {
                    Schema::table($table, fn (Blueprint $blueprint) => $blueprint->renameColumn($to, $from));
                }
            }
        }
        foreach (array_reverse(self::TABLES, true) as $from => $to) {
            Schema::rename($to, $from);
        }
    }
};
