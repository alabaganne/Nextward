<?php

use App\Support\LanguageGuess;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Many sources leave the language out; guess it so each listing knows which page shows its original text.
    public function up(): void
    {
        DB::table('internships')->whereNull('language')->select('id', 'title', 'description')->orderBy('id')
            ->chunkById(500, function ($rows) {
                foreach ($rows as $row) {
                    if ($language = LanguageGuess::guess($row->title."\n".$row->description)) {
                        DB::table('internships')->where('id', $row->id)->update(['language' => $language]);
                    }
                }
            });
    }

    public function down(): void {}
};
