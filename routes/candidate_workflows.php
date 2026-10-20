<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\ApplicationKitController;
use App\Http\Controllers\OpportunityActivityController;
use App\Http\Controllers\ResumeController;
use App\Http\Controllers\ResumeTranslationController;
use Illuminate\Support\Facades\Route;

Route::prefix('app')->middleware('auth')->group(function () {
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
    Route::get('/profile/resume', [ResumeController::class, 'main'])->name('profile.resume.show');
    Route::post('/cv/preview', [ResumeController::class, 'preview'])->middleware('throttle:120,1,cv-preview')->name('cv.preview');
    Route::get('/resumes/new', [ResumeController::class, 'create'])->name('resumes.create');
    Route::post('/resumes', [ResumeController::class, 'store'])->middleware('throttle:10,1')->name('resumes.store');
    Route::post('/resumes/from-profile', [ResumeController::class, 'fromProfile'])->middleware('throttle:20,1,resume-render')->name('resumes.profile');
    Route::get('/resumes/{resume}', [ResumeController::class, 'show'])->whereNumber('resume')->name('resumes.show');
    Route::put('/resumes/{resume}', [ResumeController::class, 'update'])->whereNumber('resume')->middleware('throttle:30,1,resume-render')->name('resumes.update');
    Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy'])->whereNumber('resume')->name('resumes.destroy');
    Route::post('/resumes/{resume}/main', [ResumeController::class, 'makeMain'])->whereNumber('resume')->name('resumes.main');
    Route::post('/resumes/{resume}/refresh', [ResumeController::class, 'refresh'])->whereNumber('resume')->middleware('throttle:20,1,resume-render')->name('resumes.refresh');
    Route::get('/resumes/{resume}/download', [ResumeController::class, 'download'])->whereNumber('resume')->name('resumes.download');

    // AI tools spend credits, so they need a verified email.
    Route::middleware('verified')->group(function () {
        Route::post('/profile/resume/read', [ResumeController::class, 'read'])->middleware('throttle:5,60,resume-read')->name('profile.resume.read');
        Route::post('/resumes/{resume}/translate', [ResumeTranslationController::class, 'store'])->whereNumber('resume')->middleware('throttle:10,60,resume-translations')->name('resumes.translate');
        Route::post('/resumes/{resume}/copy', [ResumeTranslationController::class, 'copy'])->whereNumber('resume')->middleware('throttle:10,60,resume-translations')->name('resumes.copy');
        Route::post('/resumes/{resume}/regenerate', [ResumeTranslationController::class, 'regenerate'])->whereNumber('resume')->middleware('throttle:10,60,resume-translations')->name('resumes.regenerate');
        Route::post('/opportunities/{opportunity}/kit', [ApplicationKitController::class, 'store'])
            ->whereNumber('opportunity')->middleware('throttle:10,60,kits')->name('opportunities.kit');
        Route::post('/kits/{kit}/regenerate', [ApplicationKitController::class, 'regenerate'])->whereNumber('kit')->middleware('throttle:10,60,kits')->name('kits.regenerate');
    });
    Route::get('/kits', [ApplicationKitController::class, 'index'])->name('kits.index');
    Route::get('/kits/{kit}', [ApplicationKitController::class, 'show'])->whereNumber('kit')->name('kits.show');
    Route::put('/kits/{kit}', [ApplicationKitController::class, 'update'])->whereNumber('kit')->name('kits.update');
    Route::delete('/kits/{kit}', [ApplicationKitController::class, 'destroy'])->whereNumber('kit')->name('kits.destroy');
    Route::get('/kits/{kit}/cv', [ApplicationKitController::class, 'cv'])->whereNumber('kit')->name('kits.cv');
    Route::get('/kits/{kit}/letter', [ApplicationKitController::class, 'letter'])->whereNumber('kit')->name('kits.letter');
});
