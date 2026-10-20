<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // A candidate keeps several resumes: uploaded PDFs, exports of their profile and AI translations. One is the main one.
        Schema::create('resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('translated_from_id')->nullable()->constrained('resumes')->nullOnDelete();
            $table->string('name', 150);
            $table->string('locale', 5)->nullable();
            $table->string('source', 20);
            $table->string('status', 20)->default('ready');
            $table->json('style')->nullable();
            $table->string('path')->nullable();
            $table->string('file_name')->nullable();
            $table->json('content')->nullable();
            $table->boolean('is_main')->default(false);
            $table->string('error')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedSmallInteger('generations')->default(0);
            $table->unsignedSmallInteger('credits_used')->default(0);
            $table->timestamps();
            $table->index(['candidate_id', 'is_main']);
        });

        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->foreignId('resume_id')->nullable()->after('application_kit_id')->constrained()->nullOnDelete();
        });

        Schema::table('application_kits', function (Blueprint $table) {
            $table->json('style')->nullable()->after('cover_letter');
        });

        DB::table('candidates')->whereNotNull('resume_path')->select('id', 'resume_path', 'resume_name')->orderBy('id')->chunkById(500, function ($candidates) {
            DB::table('resumes')->insert($candidates->map(fn ($candidate) => [
                'candidate_id' => $candidate->id,
                'name' => Str::limit(pathinfo($candidate->resume_name ?: 'Resume', PATHINFO_FILENAME) ?: 'Resume', 150, ''),
                'source' => 'uploaded',
                'status' => 'ready',
                'path' => $candidate->resume_path,
                'file_name' => $candidate->resume_name ?: 'resume.pdf',
                'is_main' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ])->all());
        });

        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['resume_path', 'resume_name']);
        });
    }

    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('resume_path')->nullable();
            $table->string('resume_name')->nullable();
        });

        DB::table('resumes')->where('is_main', true)->whereNotNull('path')->orderBy('id')->each(function ($resume) {
            DB::table('candidates')->where('id', $resume->candidate_id)->update(['resume_path' => $resume->path, 'resume_name' => $resume->file_name]);
        });

        Schema::table('application_kits', fn (Blueprint $table) => $table->dropColumn('style'));
        Schema::table('credit_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('resume_id');
        });
        Schema::dropIfExists('resumes');
    }
};
