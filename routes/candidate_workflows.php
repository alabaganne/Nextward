<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationKitController;
use App\Http\Controllers\OpportunityActivityController;
use App\Http\Controllers\ResumeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::middleware('throttle:120,1,activity')->group(function () {
        Route::post('/opportunities/{opportunity}/opened', [OpportunityActivityController::class, 'opened'])
            ->whereNumber('opportunity')->name('opportunities.opened');
        Route::post('/activity/sync', [OpportunityActivityController::class, 'sync'])->name('activity.sync');
    });
    Route::post('/opportunities/{opportunity}/track', [ApplicationController::class, 'track'])
        ->whereNumber('opportunity')->name('applications.track');
    Route::patch('/applications/{application}/tracking', [ApplicationController::class, 'updateTracking'])
        ->whereNumber('application')->name('applications.tracking.update');
    Route::get('/applications/{application}/attachments/{attachment}', [ApplicationController::class, 'downloadAttachment'])
        ->whereNumber('application')->whereNumber('attachment')->name('applications.attachments.download');
    Route::get('/profile/resume', [ResumeController::class, 'show'])->name('profile.resume.show');
    Route::post('/profile/resume', [ResumeController::class, 'store'])->middleware('throttle:10,1')->name('profile.resume.store');
    Route::delete('/profile/resume', [ResumeController::class, 'destroy'])->name('profile.resume.destroy');
    Route::post('/profile/resume/read', [ResumeController::class, 'read'])->middleware('throttle:5,60,resume-read')->name('profile.resume.read');
    Route::post('/opportunities/{opportunity}/kit', [ApplicationKitController::class, 'store'])
        ->whereNumber('opportunity')->middleware('throttle:10,60,kits')->name('opportunities.kit');
    Route::get('/kits', [ApplicationKitController::class, 'index'])->name('kits.index');
    Route::get('/kits/{kit}', [ApplicationKitController::class, 'show'])->whereNumber('kit')->name('kits.show');
    Route::put('/kits/{kit}', [ApplicationKitController::class, 'update'])->whereNumber('kit')->name('kits.update');
    Route::delete('/kits/{kit}', [ApplicationKitController::class, 'destroy'])->whereNumber('kit')->name('kits.destroy');
    Route::post('/kits/{kit}/regenerate', [ApplicationKitController::class, 'regenerate'])->whereNumber('kit')->middleware('throttle:10,60,kits')->name('kits.regenerate');
    Route::get('/kits/{kit}/cv', [ApplicationKitController::class, 'cv'])->whereNumber('kit')->name('kits.cv');
    Route::get('/kits/{kit}/letter', [ApplicationKitController::class, 'letter'])->whereNumber('kit')->name('kits.letter');
});
