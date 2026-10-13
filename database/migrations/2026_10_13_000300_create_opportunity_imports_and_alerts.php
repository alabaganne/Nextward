<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('import_identity', 191)->nullable()->unique();
            $table->foreignId('claimed_by_company_id')->nullable()->constrained('companies')->nullOnDelete();
        });
        Schema::create('import_sources', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('provider', 24);
            $table->string('url', 2048);
            $table->json('allowed_domains');
            $table->string('permission_status', 24)->default('pending');
            $table->text('permission_notes')->nullable();
            $table->string('publication_mode', 16)->default('review');
            $table->boolean('enabled')->default(false);
            $table->json('settings')->nullable();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('cadence_hours')->default(24);
            $table->unsignedInteger('request_delay_ms')->default(1000);
            $table->unsignedInteger('max_requests')->default(100);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('last_complete_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamps();
        });

        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->string('status', 24)->default('running');
            $table->boolean('complete')->default(false);
            $table->unsignedInteger('discovered')->default(0);
            $table->unsignedInteger('processed')->default(0);
            $table->unsignedInteger('published')->default(0);
            $table->unsignedInteger('closed')->default(0);
            $table->unsignedInteger('rejected')->default(0);
            $table->unsignedInteger('requests')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('opportunity_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_source_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->nullable()->constrained()->nullOnDelete();
            $table->string('external_id', 191);
            $table->string('source_url', 2048);
            $table->string('application_url', 2048)->nullable();
            $table->json('payload')->nullable();
            $table->json('normalized_data')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->string('state', 24)->default('pending')->index();
            $table->text('error')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_discovered_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('missing_since')->nullable();
            $table->unsignedInteger('missing_runs')->default(0);
            $table->timestamps();
            $table->unique(['import_source_id', 'external_id']);
            $table->index(['import_source_id', 'last_verified_at']);
        });

        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->json('filters');
            $table->string('frequency', 12)->default('off');
            $table->string('timezone', 64)->default('Africa/Tunis');
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('opportunity_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('saved_search_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 16)->default('pending');
            $table->uuid('batch_id')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('attempted_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'internship_id']);
        });

        Schema::create('opportunity_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reason', 40);
            $table->text('details')->nullable();
            $table->string('status', 16)->default('open');
            $table->timestamps();
        });

        Schema::create('opportunity_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('internship_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('import_source_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 64);
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('company_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('evidence');
            $table->string('status', 16)->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamps();
            $table->unique(['company_id', 'user_id']);
        });

        Schema::create('indexing_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 2048);
            $table->string('type', 24);
            $table->string('status', 16)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('available_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });

        if (! Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }
    }

    public function down(): void
    {
        foreach (['indexing_notifications', 'company_claims', 'opportunity_audits', 'opportunity_reports', 'opportunity_deliveries', 'saved_searches', 'opportunity_sources', 'import_runs', 'import_sources'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('companies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('claimed_by_company_id');
            $table->dropUnique(['import_identity']);
            $table->dropColumn('import_identity');
        });
        // Queue storage may predate this migration and is deliberately retained.
    }
};
