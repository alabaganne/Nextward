<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A tailored CV and cover letter for one candidate, one role and one language.
        Schema::create('application_kits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 5);
            $table->string('status', 20)->default('queued');
            $table->json('cv')->nullable();
            $table->longText('cover_letter')->nullable();
            $table->string('error')->nullable();
            $table->string('model')->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedSmallInteger('generations')->default(0);
            $table->unsignedSmallInteger('credits_used')->default(0);
            $table->timestamps();
            $table->unique(['student_id', 'internship_id', 'locale']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20);
            $table->string('pack', 30);
            $table->unsignedSmallInteger('credits');
            $table->unsignedInteger('amount');
            $table->string('currency', 3);
            $table->string('status', 20)->default('pending');
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
            $table->unique(['gateway', 'provider_ref']);
        });

        // Every credit added or used; the balance is the sum.
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->string('reason', 20);
            $table->foreignId('application_kit_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['user_id', 'reason']);
        });

        // Every candidate starts with one free credit, ready for when paid features open.
        DB::table('users')->where('userable_type', 'student')->select('id')->orderBy('id')->chunkById(500, function ($users) {
            DB::table('credit_transactions')->insert($users->map(fn ($user) => [
                'user_id' => $user->id, 'amount' => (int) config('billing.signup_credits', 1), 'reason' => 'welcome', 'created_at' => now(),
            ])->all());
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('application_kits');
    }
};
