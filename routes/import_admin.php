<?php

use App\Http\Controllers\ImportAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin/imports')->group(function () {
    Route::get('/', [ImportAdminController::class, 'index'])->name('imports.index');
    Route::post('/sources', [ImportAdminController::class, 'storeSource'])->name('imports.sources.store');
    Route::put('/sources/{source}', [ImportAdminController::class, 'updateSource'])->name('imports.sources.update');
    Route::post('/sources/{source}/run', [ImportAdminController::class, 'runSource'])->middleware('throttle:10,1')->name('imports.sources.run');
    Route::post('/items/{item}/publish', [ImportAdminController::class, 'publish'])->name('imports.items.publish');
    Route::post('/items/{item}/reject', [ImportAdminController::class, 'reject'])->name('imports.items.reject');
    Route::put('/opportunities/{internship}', [ImportAdminController::class, 'moderate'])->name('imports.opportunities.moderate');
    Route::post('/opportunities/{internship}/merge', [ImportAdminController::class, 'merge'])->name('imports.opportunities.merge');
    Route::put('/reports/{report}', [ImportAdminController::class, 'resolveReport'])->name('imports.reports.resolve');
    Route::put('/claims/{claim}', [ImportAdminController::class, 'reviewClaim'])->name('imports.claims.review');
});
