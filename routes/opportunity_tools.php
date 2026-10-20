<?php

use App\Http\Controllers\OpportunityFeedbackController;
use Illuminate\Support\Facades\Route;

Route::post('/opportunities/{opportunity}/report', [OpportunityFeedbackController::class, 'report'])
    ->whereNumber('opportunity')->middleware('throttle:5,1,report')->name('opportunities.report');
Route::post('/app/companies/{company}/claim', [OpportunityFeedbackController::class, 'claim'])
    ->whereNumber('company')->middleware(['auth', 'verified', 'throttle:5,1,claim'])->name('companies.claim');

require __DIR__.'/saved_searches.php';
if (is_file(__DIR__.'/import_admin.php')) {
    require __DIR__.'/import_admin.php';
}
