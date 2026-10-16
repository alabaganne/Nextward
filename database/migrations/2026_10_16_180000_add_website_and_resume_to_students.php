<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('website')->nullable()->after('location');
            $table->string('resume_path')->nullable()->after('website');
            $table->string('resume_name')->nullable()->after('resume_path');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['website', 'resume_path', 'resume_name']);
        });
    }
};
