<?php

use App\Actions\OutpatientForms\GenerateOutpatientFormPdf;
use App\Models\OutpatientForm;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('outpatient-forms', 'pages::outpatient-forms.index')->name('outpatient-forms.index');
    Route::livewire('outpatient-forms/create', 'pages::outpatient-forms.edit')->name('outpatient-forms.create');
    Route::livewire('outpatient-forms/{outpatientForm}', 'pages::outpatient-forms.show')->name('outpatient-forms.show');
    Route::livewire('outpatient-forms/{outpatientForm}/edit', 'pages::outpatient-forms.edit')->name('outpatient-forms.edit');

    Route::get('outpatient-forms/{outpatientForm}/pdf/{document}', function (OutpatientForm $outpatientForm, string $document) {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $filename = sprintf('%s-%s.pdf', $document, Str::slug($outpatientForm->patient_name));

        return response((new GenerateOutpatientFormPdf)($outpatientForm, $document), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('%s; filename="%s"', request()->boolean('download') ? 'attachment' : 'inline', $filename),
        ]);
    })
        ->whereIn('document', [...array_keys(OutpatientForm::documents()), OutpatientForm::DOCUMENT_ALL])
        ->name('outpatient-forms.pdf');
});
