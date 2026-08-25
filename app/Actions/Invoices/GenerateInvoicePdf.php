<?php

namespace App\Actions\Invoices;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateInvoicePdf
{
    /**
     * Render the invoice to a PDF and store it, returning the storage path.
     */
    public function __invoke(Invoice $invoice): string
    {
        $invoice->loadMissing(['homeHealthAgency', 'createdBy', 'items.visit.patient']);

        $pdf = Pdf::loadView('pdf.invoices.invoice', [
            'invoice' => $invoice,
        ]);

        $path = sprintf('invoices/%d.pdf', $invoice->id);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
