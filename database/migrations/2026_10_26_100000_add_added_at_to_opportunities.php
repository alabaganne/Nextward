<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// When a listing went live on Nextward, kept next to posted_at (when the employer posted it).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->timestamp('added_at')->nullable()->index();
        });
        DB::table('opportunities')->where('origin', 'imported')->update(['added_at' => DB::raw('created_at')]);
        DB::table('opportunities')->where('origin', '!=', 'imported')->where('publication_status', 'published')
            ->update(['added_at' => DB::raw('COALESCE(posted_at, created_at)')]);
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['added_at']);
            $table->dropColumn('added_at');
        });
    }
};
