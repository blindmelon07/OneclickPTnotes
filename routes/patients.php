<?php

use App\Actions\Patients\GeneratePatientChartPdf;
use App\Models\Note;
use App\Models\Patient;
use App\Models\PatientAttachment;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('calendar', 'pages::calendar')
        ->middleware('unless_hidden:'.User::HIDE_CALENDAR)
        ->name('calendar');
    Route::livewire('patients', 'pages::patients.index')->name('patients.index');
    Route::livewire('patients/{patient}', 'pages::patients.show')->name('patients.show');
    Route::livewire('patients/{patient}/notes/create/{type}', 'pages::patients.notes.create')->name('patients.notes.create');
    Route::livewire('notes/{note}', 'pages::patients.notes.show')->name('notes.show');
    Route::livewire('patients/{patient}/visits/{visit}/document', 'pages::patients.visits.document')->name('patients.visits.document');

    Route::get('notes/{note}/download', function (Note $note) {
        abort_unless($note->patient->isVisibleTo(auth()->user()), 403);
        abort_unless($note->pdf_path && Storage::disk('local')->exists($note->pdf_path), 404);

        return Storage::disk('local')->download(
            $note->pdf_path,
            sprintf('%s-%s.pdf', $note->type, $note->patient->name)
        );
    })->name('notes.download');

    Route::get('notes/{note}/signature', function (Note $note) {
        abort_unless($note->patient->isVisibleTo(auth()->user()), 403);
        abort_unless($note->signature_path && Storage::disk('local')->exists($note->signature_path), 404);

        return Storage::disk('local')->response($note->signature_path);
    })->name('notes.signature');

    Route::get('notes/{note}/patient-signature', function (Note $note) {
        abort_unless($note->patient->isVisibleTo(auth()->user()), 403);
        abort_unless($note->patient_signature_path && Storage::disk('local')->exists($note->patient_signature_path), 404);

        return Storage::disk('local')->response($note->patient_signature_path);
    })->name('notes.patient-signature');

    Route::get('patients/{patient}/chart/download', function (Patient $patient) {
        abort_unless($patient->isVisibleTo(auth()->user()), 403);

        $path = (new GeneratePatientChartPdf)($patient);

        activity('patient')
            ->performedOn($patient)
            ->causedBy(auth()->user())
            ->log('chart exported');

        return Storage::disk('local')->download($path, sprintf('%s-chart.pdf', $patient->name));
    })->name('patients.chart.download');

    Route::get('attachments/{attachment}/download', function (PatientAttachment $attachment) {
        abort_unless($attachment->patient->isVisibleTo(auth()->user()), 403);
        abort_unless(Storage::disk('local')->exists($attachment->path), 404);

        activity('patient')
            ->performedOn($attachment)
            ->causedBy(auth()->user())
            ->log('attachment downloaded');

        return Storage::disk('local')->download($attachment->path, $attachment->original_filename);
    })->name('attachments.download');

    Route::middleware('unless_hidden:'.User::HIDE_DIRECTORIES)->group(function () {
        Route::livewire('doctors', 'pages::doctors.index')->name('doctors.index');
        Route::livewire('home-health-agencies', 'pages::home-health-agencies.index')->name('home-health-agencies.index');
        Route::livewire('insurance-companies', 'pages::insurance-companies.index')->name('insurance-companies.index');
    });
});
