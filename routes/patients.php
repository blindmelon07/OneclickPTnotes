<?php

use App\Models\Note;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('patients/{patient}', 'pages::patients.show')->name('patients.show');
    Route::livewire('patients/{patient}/notes/create/{type}', 'pages::patients.notes.create')->name('patients.notes.create');
    Route::livewire('notes/{note}', 'pages::patients.notes.show')->name('notes.show');

    Route::get('notes/{note}/download', function (Note $note) {
        abort_unless($note->pdf_path && Storage::disk('local')->exists($note->pdf_path), 404);

        return Storage::disk('local')->download(
            $note->pdf_path,
            sprintf('%s-%s.pdf', $note->type, $note->patient->name)
        );
    })->name('notes.download');

    Route::get('notes/{note}/signature', function (Note $note) {
        abort_unless($note->signature_path && Storage::disk('local')->exists($note->signature_path), 404);

        return Storage::disk('local')->response($note->signature_path);
    })->name('notes.signature');

    Route::livewire('doctors', 'pages::doctors.index')->name('doctors.index');
    Route::livewire('home-health-agencies', 'pages::home-health-agencies.index')->name('home-health-agencies.index');
    Route::livewire('insurance-companies', 'pages::insurance-companies.index')->name('insurance-companies.index');
});
