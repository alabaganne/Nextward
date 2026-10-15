<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\OpportunityActivityController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::middleware('throttle:120,1')->group(function () {
        Route::post('/opportunities/{internship}/opened', [OpportunityActivityController::class, 'opened'])
            ->whereNumber('internship')->name('opportunities.opened');
        Route::post('/activity/sync', [OpportunityActivityController::class, 'sync'])->name('activity.sync');
    });
    Route::post('/opportunities/{internship}/track', [ApplicationController::class, 'track'])
        ->whereNumber('internship')->name('applications.track');
    Route::patch('/applications/{application}/tracking', [ApplicationController::class, 'updateTracking'])
        ->whereNumber('application')->name('applications.tracking.update');
    Route::get('/applications/{application}/attachments/{attachment}', [ApplicationController::class, 'downloadAttachment'])
        ->whereNumber('application')->whereNumber('attachment')->name('applications.attachments.download');
});
