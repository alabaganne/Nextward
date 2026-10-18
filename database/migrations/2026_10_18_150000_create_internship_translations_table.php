<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internship_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('title');
            $table->longText('description');
            $table->string('slug', 190);
            $table->char('source_hash', 40);
            $table->string('model')->nullable();
            $table->timestamps();
            $table->unique(['internship_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('internship_translations');
    }
};
