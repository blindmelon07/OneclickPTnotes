<?php

namespace App\Actions\OutpatientForms;

use App\Models\OutpatientForm;
use Barryvdh\DomPDF\Facade\Pdf;

class GenerateOutpatientFormPdf
{
    /**
     * Render one of the case's forms — or all three, a page each — to PDF
     * bytes. Rendered on demand rather than stored, since the forms are still
     * being reviewed and change with every edit.
     */
    public function __invoke(OutpatientForm $outpatientForm, string $document): string
    {
        $documents = $document === OutpatientForm::DOCUMENT_ALL
            ? array_keys(OutpatientForm::documents())
            : [$document];

        return Pdf::loadView('pdf.outpatient-forms.document', [
            'form' => $outpatientForm,
            'data' => $outpatientForm->formData(),
            'documents' => $documents,
        ])->output();
    }
}
