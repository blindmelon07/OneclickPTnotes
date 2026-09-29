<?php

namespace App\Actions\StagingForms;

use App\Models\StagingForm;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateStagingFormPdf
{
    /**
     * Render one of the staging form's documents — or every one of them, a
     * page each — to PDF bytes. Rendered on demand rather than stored, since
     * the forms are still being reviewed and change with every edit.
     */
    public function __invoke(StagingForm $stagingForm, string $document): string
    {
        $documents = $document === StagingForm::DOCUMENT_ALL
            ? array_keys(StagingForm::documents())
            : [$document];

        return Pdf::loadView('pdf.staging-forms.document', [
            'form' => $stagingForm,
            'data' => $stagingForm->formData(),
            'documents' => $documents,
        ])->output();
    }
}
