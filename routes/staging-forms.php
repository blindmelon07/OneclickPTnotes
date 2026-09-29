<?php

use App\Actions\StagingForms\GenerateStagingFormPdf;
use App\Models\StagingForm;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('staging-forms', 'pages::staging-forms.index')->name('staging-forms.index');
    Route::livewire('staging-forms/create', 'pages::staging-forms.edit')->name('staging-forms.create');
    Route::livewire('staging-forms/{stagingForm}', 'pages::staging-forms.show')->name('staging-forms.show');
    Route::livewire('staging-forms/{stagingForm}/edit', 'pages::staging-forms.edit')->name('staging-forms.edit');

    Route::get('staging-forms/{stagingForm}/pdf/{document}', function (StagingForm $stagingForm, string $document) {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $filename = sprintf('%s-%s.pdf', $document, Str::slug($stagingForm->patient_name));

        return response((new GenerateStagingFormPdf)($stagingForm, $document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', request()->boolean('download') ? 'attachment' : 'inline', $filename),
        ]);
    })
        ->whereIn('document', [...array_keys(StagingForm::documents()), StagingForm::DOCUMENT_ALL])
        ->name('staging-forms.pdf');
});
