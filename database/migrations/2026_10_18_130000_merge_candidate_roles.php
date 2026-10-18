<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admins are candidates too: give each one without a profile a candidate profile.
        DB::table('users')->where('is_admin', true)->whereNull('userable_id')->orderBy('id')->each(function ($user) {
            $id = DB::table('students')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
            DB::table('users')->where('id', $user->id)->update(['userable_type' => 'student', 'userable_id' => $id]);
        });

        Schema::table('students', fn (Blueprint $table) => $table->dropColumn('career_stage'));
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $table) => $table->string('career_stage')->default('student'));
    }
};
