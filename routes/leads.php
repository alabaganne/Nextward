<?php

use App\Http\Controllers\LeadController;
use App\Http\Controllers\LeadGmailController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('app/leads')->group(function () {
    Route::get('/', [LeadController::class, 'index'])->name('leads.index');
    Route::post('/', [LeadController::class, 'store'])->name('leads.store');
    Route::post('/import', [LeadController::class, 'import'])->middleware('throttle:10,1')->name('leads.import');
    Route::get('/gmail/connect', [LeadGmailController::class, 'connect'])->name('leads.gmail.connect');
    Route::get('/gmail/callback', [LeadGmailController::class, 'callback'])->name('leads.gmail.callback');
    Route::post('/gmail/sync', [LeadGmailController::class, 'sync'])->middleware('throttle:5,1')->name('leads.gmail.sync');
    Route::delete('/gmail', [LeadGmailController::class, 'disconnect'])->name('leads.gmail.disconnect');
    Route::put('/{lead}', [LeadController::class, 'update'])->name('leads.update');
    Route::get('/{lead}/open', [LeadController::class, 'open'])->name('leads.open');
    Route::post('/{lead}/contacts', [LeadController::class, 'storeContact'])->name('leads.contacts.store');
    Route::post('/{lead}/contacts/suggest', [LeadController::class, 'suggestContact'])->middleware('throttle:10,1')->name('leads.contacts.suggest');
    Route::put('/contacts/{contact}', [LeadController::class, 'updateContact'])->name('leads.contacts.update');
    Route::delete('/contacts/{contact}', [LeadController::class, 'destroyContact'])->name('leads.contacts.destroy');
});
