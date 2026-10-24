<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_profiles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->json('data');
            $table->timestamps();
        });

        // Nextward no longer reads Tunisian job sites. Their open listings are closed so they show as archived.
        $sources = DB::table('import_sources')->where(fn ($q) => $q->whereIn('key', ['tanitjobs', 'keejob'])
            ->orWhere('url', 'like', '%.tn/%'))->pluck('id');
        if ($sources->isEmpty()) { return; }
        DB::table('import_sources')->whereIn('id', $sources)->update(['enabled' => false, 'permission_status' => 'revoked', 'updated_at' => now(),
            'last_error' => 'Turned off: Nextward no longer reads Tunisian job sites.']);
        $roles = DB::table('opportunity_sources')->whereIn('import_source_id', $sources)->whereNotNull('opportunity_id')->pluck('opportunity_id');
        $kept = DB::table('opportunity_sources')->whereIn('opportunity_id', $roles)->whereNotIn('import_source_id', $sources)
            ->where('state', 'published')->pluck('opportunity_id');
        DB::table('opportunities')->whereIn('id', $roles->diff($kept))->where('origin', 'imported')->where('status', 'open')
            ->update(['status' => 'closed', 'content_updated_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('search_profiles');
    }
};
