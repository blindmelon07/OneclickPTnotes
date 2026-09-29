<?php

namespace App\Actions\Patients;

use App\Models\Patient;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GeneratePatientChartPdf
{
    /**
     * Render every signed note (with any addenda) plus the attachment list
     * for a patient into a single PDF, storing and overwriting the same
     * path each time so exports don't pile up stale snapshots.
     *
     * This does not merge the individually-signed note PDFs byte-for-byte —
     * it re-renders the same underlying data (fields, signatures, addenda)
     * into one document, which keeps the whole-chart export dependency-free.
     */
    public function __invoke(Patient $patient): string
    {
        $patient->loadMissing([
            'doctor', 'insuranceCompany', 'homeHealthAgency',
            'notes' => fn ($query) => $query->whereNotNull('signed_at')->oldest('signed_at'),
            'notes.author',
            'notes.addenda.author',
            'attachments.uploadedBy',
        ]);

        $pdf = Pdf::loadView('pdf.patients.chart', [
            'patient' => $patient,
            'signatureDataUri' => fn (?string $path) => $this->signatureDataUri($path),
        ]);

        $path = sprintf('patients/%d/chart.pdf', $patient->id);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Read a stored signature PNG and inline it as a base64 data URI.
     */
    protected function signatureDataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return 'data:image/png;base64,'.base64_encode(Storage::disk('local')->get($path));
    }
}
