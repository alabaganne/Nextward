<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->timestamp('verified_at')->nullable()->index();
            $table->json('verification')->nullable();
            $table->timestamp('researched_at')->nullable();
            $table->unsignedTinyInteger('research_attempts')->default(0);
            $table->string('stage', 12)->nullable();
            $table->string('size', 20)->nullable();
            $table->unsignedSmallInteger('founded_year')->nullable();
            $table->string('linkedin_url', 500)->nullable();
            $table->string('careers_url', 500)->nullable();
        });
        Schema::table('opportunity_sources', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['verified_at']);
            $table->dropColumn(['verified_at', 'verification', 'researched_at', 'research_attempts', 'stage', 'size', 'founded_year', 'linkedin_url', 'careers_url']);
        });
    }
};
