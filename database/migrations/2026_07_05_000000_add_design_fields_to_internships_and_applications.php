<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bring the schema up to what the Internly design screens display
     * (design/INTEGRATION.md §6).
     */
    public function up(): void
    {
        Schema::table('internships', function (Blueprint $table) {
            $table->string('work_type')->default('onsite')->after('description'); // remote|hybrid|onsite
            $table->unsignedSmallInteger('duration_weeks')->nullable()->after('work_type');
            $table->string('term')->nullable()->after('duration_weeks'); // e.g. "Summer 2026"
            $table->unsignedInteger('pay_amount')->nullable()->after('term');
            $table->string('pay_unit')->nullable()->after('pay_amount'); // hour|month
            $table->string('status')->default('open')->after('pay_unit'); // open|closed
        });

        // applications.status: legacy nullable boolean -> string enum
        // (submitted|viewed|review|interview|offer|closed).
        Schema::table('applications', function (Blueprint $table) {
            $table->string('stage')->default('submitted')->after('status');
        });

        DB::table('applications')->where('status', true)->update(['stage' => 'offer']);
        DB::table('applications')->where('status', false)->update(['stage' => 'closed']);
        DB::table('applications')->whereNull('status')->update(['stage' => 'submitted']);

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->renameColumn('stage', 'status');
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->boolean('legacy_status')->nullable()->after('status');
        });

        DB::table('applications')->whereIn('status', ['offer', 'accepted'])->update(['legacy_status' => true]);
        DB::table('applications')->whereIn('status', ['closed', 'rejected'])->update(['legacy_status' => false]);

        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->renameColumn('legacy_status', 'status');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->dropColumn(['work_type', 'duration_weeks', 'term', 'pay_amount', 'pay_unit', 'status']);
        });
    }
};
