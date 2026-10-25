<?php

use App\Http\Controllers\ImportAdminController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('app/admin/imports')->group(function () {
    Route::get('/', [ImportAdminController::class, 'index'])->name('imports.index');
    Route::get('/sources', [ImportAdminController::class, 'sources'])->name('imports.sources.index');
    Route::get('/review', [ImportAdminController::class, 'review'])->name('imports.review');
    Route::get('/reports', [ImportAdminController::class, 'reports'])->name('imports.reports');
    Route::get('/claims', [ImportAdminController::class, 'claims'])->name('imports.claims');
    Route::get('/runs', [ImportAdminController::class, 'runs'])->name('imports.runs');
    Route::get('/opportunities/{opportunity}', [ImportAdminController::class, 'opportunity'])->name('imports.opportunities.show');
    Route::get('/sources/create', [ImportAdminController::class, 'createSource'])->name('imports.sources.create');
    Route::post('/sources', [ImportAdminController::class, 'storeSource'])->name('imports.sources.store');
    Route::get('/sources/{source}/edit', [ImportAdminController::class, 'editSource'])->name('imports.sources.edit');
    Route::put('/sources/{source}', [ImportAdminController::class, 'updateSource'])->name('imports.sources.update');
    Route::post('/fetch', [ImportAdminController::class, 'fetch'])->middleware('throttle:6,1,import-fetch')->name('imports.fetch');
    Route::post('/sources/{source}/run', [ImportAdminController::class, 'runSource'])->middleware('throttle:10,1,import-run')->name('imports.sources.run');
    Route::post('/items/{item}/publish', [ImportAdminController::class, 'publish'])->name('imports.items.publish');
    Route::post('/items/{item}/reject', [ImportAdminController::class, 'reject'])->name('imports.items.reject');
    Route::put('/opportunities/{opportunity}', [ImportAdminController::class, 'moderate'])->name('imports.opportunities.moderate');
    Route::post('/opportunities/{opportunity}/merge', [ImportAdminController::class, 'merge'])->name('imports.opportunities.merge');
    Route::put('/reports/{report}', [ImportAdminController::class, 'resolveReport'])->name('imports.reports.resolve');
    Route::put('/claims/{claim}', [ImportAdminController::class, 'reviewClaim'])->name('imports.claims.review');
});
