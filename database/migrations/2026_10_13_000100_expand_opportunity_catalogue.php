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
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['city_id']);
            $table->unsignedBigInteger('city_id')->nullable()->change();
            $table->string('name')->nullable();
            $table->string('domain')->nullable()->index();
            $table->text('logo_url')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('location')->nullable();
            $table->foreign('city_id')->references('id')->on('cities')->nullOnDelete();
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropForeign(['field_id']);
            $table->unsignedBigInteger('field_id')->nullable()->change();
            $table->foreign('field_id')->references('id')->on('fields')->nullOnDelete();
            $table->date('closing_at')->nullable()->change();
            $table->string('kind', 20)->default('internship');
            $table->string('origin', 20)->default('native');
            $table->string('application_mode', 20)->default('internal');
            $table->text('application_url')->nullable();
            $table->string('slug', 180)->nullable();
            $table->string('publication_status', 20)->default('published');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->timestamp('content_updated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->string('location')->nullable();
            $table->json('locations')->nullable();
            $table->json('employment_types')->nullable();
            $table->string('seniority', 30)->nullable();
            $table->string('program_type', 40)->nullable();
            $table->date('start_date')->nullable();
            $table->string('remote_scope')->nullable();
            $table->string('remote_eligibility', 20)->default('unknown');
            $table->string('relocation', 20)->default('unknown');
            $table->string('visa_sponsorship', 20)->default('unknown');
            $table->json('eligible_countries')->nullable();
            $table->json('excluded_countries')->nullable();
            $table->json('eligibility_evidence')->nullable();
            $table->string('language', 10)->nullable();
            $table->decimal('pay_min', 12, 2)->nullable();
            $table->decimal('pay_max', 12, 2)->nullable();
            $table->char('pay_currency', 3)->nullable();
            $table->string('compensation_status', 20)->default('unspecified');
            $table->json('moderation_overrides')->nullable();
            $table->foreignId('merged_into_id')->nullable()->constrained('internships')->nullOnDelete();
            $table->index(['publication_status', 'status', 'kind', 'posted_at'], 'opportunities_browse_index');
            $table->index(['work_type', 'remote_eligibility'], 'opportunities_remote_index');
            $table->index('expires_at');
        });

        DB::table('internships')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                DB::table('internships')->where('id', $row->id)->update([
                    'slug' => Str::limit(Str::slug($row->title), 180, '') ?: 'opportunity',
                    'posted_at' => $row->created_at,
                    'first_seen_at' => $row->created_at,
                    'content_updated_at' => $row->updated_at,
                    'pay_min' => $row->pay_amount,
                    'pay_max' => $row->pay_amount,
                    'compensation_status' => $row->pay_amount > 0 ? 'paid' : 'unspecified',
                ]);
            }
        });

        DB::table('companies')->orderBy('id')->chunkById(200, function ($rows) {
            foreach ($rows as $row) {
                $name = DB::table('users')->where('userable_type', 'company')->where('userable_id', $row->id)->value('name');
                DB::table('companies')->where('id', $row->id)->update(['name' => $name]);
            }
        });

        // Only cities explicitly present in the original Tunisian city seed justify a country backfill.
        // The old "Remote" entry deliberately remains unknown.
        $tunisianCities = DB::table('cities')->whereIn('name', [
            'Tunis', 'Sfax', 'Sousse', 'Kairouan', 'Bizerte', 'Gabès', 'Ariana', 'Gafsa',
            'Monastir', 'Ben Arous', 'Nabeul', 'Mahdia', 'Médenine', 'Jendouba', 'Kasserine',
        ])->pluck('id');
        DB::table('companies')->whereIn('city_id', $tunisianCities)->update(['country_code' => 'TN']);
        DB::table('internships')->whereIn('city_id', $tunisianCities)->update(['country_code' => 'TN']);
    }

    public function down(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->dropForeign(['merged_into_id']);
            $table->dropIndex('opportunities_browse_index');
            $table->dropIndex('opportunities_remote_index');
            $table->dropIndex(['country_code']);
            $table->dropIndex(['expires_at']);
            $table->dropColumn([
                'kind', 'origin', 'application_mode', 'application_url', 'slug',
                'publication_status', 'posted_at', 'first_seen_at', 'last_verified_at',
                'content_updated_at', 'expires_at', 'country_code', 'location', 'locations',
                'employment_types', 'seniority', 'program_type', 'start_date', 'remote_scope',
                'remote_eligibility', 'relocation', 'visa_sponsorship', 'eligible_countries',
                'excluded_countries', 'eligibility_evidence', 'language', 'pay_min', 'pay_max',
                'pay_currency', 'compensation_status', 'moderation_overrides', 'merged_into_id',
            ]);
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['domain']);
            $table->dropColumn(['name', 'domain', 'logo_url', 'country_code', 'location']);
        });

        // Nullable taxonomy and deadlines remain compatible with data created after this migration.
    }
};
