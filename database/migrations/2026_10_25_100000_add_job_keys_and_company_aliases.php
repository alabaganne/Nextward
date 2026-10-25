<?php

use App\Importing\JobKey;
use App\Models\CompanyAlias;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_aliases', function (Blueprint $table) {
            $table->id();
            $table->string('alias', 191)->unique();
            $table->string('name', 191);
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
        Schema::table('companies', function (Blueprint $table) {
            $table->string('name_key', 191)->nullable()->index();
        });
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('job_key', 40)->nullable()->index();
            $table->timestamp('first_posted_at')->nullable();
            $table->string('date_source', 10)->nullable();
            $table->timestamp('reposted_at')->nullable();
            $table->unsignedTinyInteger('min_years')->nullable();
        });
        Schema::table('opportunity_sources', function (Blueprint $table) {
            $table->string('url_key', 500)->nullable()->index();
        });

        foreach (config('leads.aliases', []) as $alias => $name) { CompanyAlias::remember($alias, $name); }
        JobKey::forget();
        DB::table('companies')->select('id', 'name')->orderBy('id')->chunk(500, function ($companies) {
            foreach ($companies as $company) {
                DB::table('companies')->where('id', $company->id)->update(['name_key' => JobKey::company((string) $company->name) ?: null]);
            }
        });
        $ats = ['greenhouse', 'lever', 'ashby'];
        DB::table('opportunities')->leftJoin('companies', 'companies.id', '=', 'opportunities.company_id')
            ->select('opportunities.id', 'opportunities.title', 'opportunities.location', 'opportunities.posted_at', 'opportunities.first_seen_at', 'companies.name')
            ->orderBy('opportunities.id')->chunk(500, function ($roles) use ($ats) {
                $fromAts = DB::table('opportunity_sources')->join('import_sources', 'import_sources.id', '=', 'opportunity_sources.import_source_id')
                    ->whereIn('opportunity_sources.opportunity_id', $roles->pluck('id'))->whereIn('import_sources.provider', $ats)
                    ->pluck('opportunity_sources.opportunity_id')->flip();
                foreach ($roles as $role) {
                    DB::table('opportunities')->where('id', $role->id)->update([
                        'job_key' => JobKey::key((string) $role->name, (string) $role->title, $role->location),
                        'first_posted_at' => $role->posted_at ?? $role->first_seen_at,
                        'date_source' => $role->posted_at ? (isset($fromAts[$role->id]) ? 'ats' : 'jsonld') : 'seen',
                    ]);
                }
            });
        DB::table('opportunity_sources')->select('id', 'source_url')->orderBy('id')->chunk(500, function ($items) {
            foreach ($items as $item) {
                DB::table('opportunity_sources')->where('id', $item->id)->update(['url_key' => JobKey::url($item->source_url)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('opportunity_sources', fn (Blueprint $table) => $table->dropColumn('url_key'));
        Schema::table('opportunities', fn (Blueprint $table) => $table->dropColumn(['job_key', 'first_posted_at', 'date_source', 'reposted_at', 'min_years']));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('name_key'));
        Schema::dropIfExists('company_aliases');
    }
};
