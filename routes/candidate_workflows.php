<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CoverLetterController;
use App\Http\Controllers\OpportunityActivityController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::middleware('throttle:120,1,activity')->group(function () {
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
    Route::get('/profile/resume', [ResumeController::class, 'show'])->name('profile.resume.show');
    Route::post('/profile/resume', [ResumeController::class, 'store'])->middleware('throttle:10,1')->name('profile.resume.store');
    Route::delete('/profile/resume', [ResumeController::class, 'destroy'])->name('profile.resume.destroy');
    Route::post('/profile/resume/read', [ResumeController::class, 'read'])->middleware('throttle:5,60,resume-read')->name('profile.resume.read');
    Route::post('/opportunities/{internship}/cover-letter', [CoverLetterController::class, 'store'])
        ->whereNumber('internship')->middleware('throttle:10,60,cover-letter')->name('opportunities.cover-letter');
});
