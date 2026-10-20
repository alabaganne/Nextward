<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Credits are on: every candidate starts with at least the sign-up amount (20).
    public function up(): void
    {
        $target = (int) config('billing.signup_credits', 20);
        DB::table('users')->where('userable_type', 'candidate')->select('id')->orderBy('id')->chunkById(500, function ($users) use ($target) {
            $balances = DB::table('credit_transactions')->whereIn('user_id', $users->pluck('id'))->groupBy('user_id')->pluck(DB::raw('SUM(amount)'), 'user_id');
            $rows = $users->map(fn ($user) => ['user_id' => $user->id, 'amount' => $target - (int) ($balances[$user->id] ?? 0), 'reason' => 'welcome', 'note' => 'Free credits top-up', 'created_at' => now()])
                ->filter(fn ($row) => $row['amount'] > 0)->values()->all();
            $rows && DB::table('credit_transactions')->insert($rows);
        });
    }

    public function down(): void
    {
        DB::table('credit_transactions')->where('reason', 'welcome')->where('note', 'Free credits top-up')->delete();
    }
};
