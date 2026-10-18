<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // null (not asked), pending, approved or declined
            $table->string('public_status', 20)->nullable()->index()->after('resume_name');
            $table->timestamp('public_reviewed_at')->nullable()->after('public_status');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropIndex(['public_status']);
            $table->dropColumn(['public_status', 'public_reviewed_at']);
        });
    }
};
