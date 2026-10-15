<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->timestamp('viewed_at');
            $table->unique(['student_id', 'internship_id']);
            $table->index(['student_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunity_views');
    }
};
