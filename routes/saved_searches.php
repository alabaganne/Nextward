<?php

use App\Http\Controllers\SavedSearchController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('/saved-searches', [SavedSearchController::class, 'index'])->name('saved-searches.index');
    Route::post('/saved-searches', [SavedSearchController::class, 'store'])->middleware(['verified', 'throttle:20,1,saved-searches'])->name('saved-searches.store');
    Route::put('/saved-searches/{savedSearch}', [SavedSearchController::class, 'update'])->name('saved-searches.update');
    Route::delete('/saved-searches/{savedSearch}', [SavedSearchController::class, 'destroy'])->name('saved-searches.destroy');
});

Route::get('/alerts/unsubscribe/{savedSearch}', [SavedSearchController::class, 'unsubscribeConfirmation'])
    ->middleware('signed')->name('saved-searches.unsubscribe');
Route::post('/alerts/unsubscribe/{savedSearch}', [SavedSearchController::class, 'unsubscribe'])
    ->middleware('signed')->name('saved-searches.unsubscribe.store');
