<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name', 191);
            $table->string('company_key', 191)->index();
            $table->string('title', 255);
            $table->string('job_key', 40);
            $table->string('url', 2000)->nullable();
            $table->json('urls')->nullable();
            $table->string('status', 16)->default('to_apply');
            $table->string('eligibility', 10)->nullable();
            $table->text('eligibility_quote')->nullable();
            $table->smallInteger('score')->default(0);
            $table->json('reasons')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->string('date_source', 10)->nullable();
            $table->timestamp('reposted_at')->nullable();
            $table->date('digest_date')->nullable()->index();
            $table->timestamp('opened_at')->nullable();
            $table->date('applied_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('origin', 12)->default('digest');
            $table->string('external_ref', 191)->nullable();
            $table->json('extra')->nullable();
            $table->timestamps();
            $table->unique(['candidate_id', 'job_key']);
            $table->unique(['candidate_id', 'origin', 'external_ref']);
        });
        Schema::create('lead_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name', 191);
            $table->string('company_key', 191)->index();
            $table->string('name', 191);
            $table->string('title', 191)->nullable();
            $table->string('profile_url', 500)->nullable();
            $table->text('note')->nullable();
            $table->string('mutual', 191)->nullable();
            $table->string('source', 8)->default('manual');
            $table->string('source_url', 500)->nullable();
            $table->timestamp('contacted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('lead_skips', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->date('run_date');
            $table->foreignId('opportunity_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opportunity_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_key', 40);
            $table->string('company_name', 191);
            $table->string('title', 255);
            $table->string('url', 2000)->nullable();
            $table->string('reason', 12);
            $table->string('detail', 500)->nullable();
            $table->timestamps();
            $table->index(['candidate_id', 'job_key']);
            $table->index(['candidate_id', 'run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_skips');
        Schema::dropIfExists('lead_contacts');
        Schema::dropIfExists('leads');
    }
};
