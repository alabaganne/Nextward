<?php

use App\Http\Controllers\OpportunityFeedbackController;
use Illuminate\Support\Facades\Route;

Route::post('/opportunities/{internship}/report', [OpportunityFeedbackController::class, 'report'])
    ->whereNumber('internship')->middleware('throttle:5,1,report')->name('opportunities.report');
Route::post('/companies/{company}/claim', [OpportunityFeedbackController::class, 'claim'])
    ->whereNumber('company')->middleware(['auth', 'verified', 'throttle:5,1,claim'])->name('companies.claim');

require __DIR__.'/saved_searches.php';
if (is_file(__DIR__.'/import_admin.php')) {
    require __DIR__.'/import_admin.php';
}
