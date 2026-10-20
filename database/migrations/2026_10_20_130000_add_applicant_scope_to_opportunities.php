<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Who can apply: anywhere, listed (some countries), local (must already be allowed to work there) or unknown.
    public function up(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->string('applicant_scope', 20)->default('unknown')->after('remote_eligibility');
            $table->index('applicant_scope');
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropIndex(['applicant_scope']);
            $table->dropColumn('applicant_scope');
        });
    }
};
