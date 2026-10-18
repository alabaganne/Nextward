<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['field_id']);
            $table->dropForeign(['city_id']);
            $table->unsignedBigInteger('field_id')->nullable()->change();
            $table->unsignedBigInteger('city_id')->nullable()->change();
            $table->foreign('field_id')->references('id')->on('fields')->nullOnDelete();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
            $table->string('career_stage')->default('student');
            $table->string('headline')->nullable();
            $table->unsignedTinyInteger('experience_years')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('location')->nullable();
            $table->json('languages')->nullable();
            $table->json('preferences')->nullable();
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->longText('cover_letter')->nullable()->change();
            $table->text('attachments')->nullable()->change();
            $table->string('channel')->default('internal')->index();
            $table->date('applied_at')->nullable();
            $table->text('candidate_notes')->nullable();
        });

        DB::table('likes')->select('student_id', 'internship_id')->groupBy('student_id', 'internship_id')
            ->havingRaw('COUNT(*) > 1')->get()->each(function ($like) {
                DB::transaction(function () use ($like) {
                    $key = ['student_id' => $like->student_id, 'internship_id' => $like->internship_id];
                    DB::table('likes')->where($key)->delete();
                    DB::table('likes')->insert($key);
                });
            });

        Schema::table('likes', fn (Blueprint $table) => $table->unique(['student_id', 'internship_id']));
    }

    public function down(): void
    {
        Schema::table('likes', fn (Blueprint $table) => $table->dropUnique(['student_id', 'internship_id']));
        Schema::table('applications', function (Blueprint $table) {
            $table->dropIndex(['channel']);
            $table->dropColumn(['channel', 'applied_at', 'candidate_notes']);
        });
        // career_stage was dropped later, so only drop the columns that still exist.
        Schema::table('students', fn (Blueprint $table) => $table->dropColumn(array_values(array_filter([
            'career_stage', 'headline', 'experience_years', 'country_code', 'location', 'languages', 'preferences',
        ], fn ($column) => Schema::hasColumn('students', $column)))));
        // Nullable taxonomy and cover letters remain compatible with existing data.
    }
};
