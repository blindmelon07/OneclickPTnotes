<?php

namespace App\Actions\Notes;

use App\Models\Note;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class GenerateNotePdf
{
    /**
     * Render the note to a PDF and store it, returning the storage path.
     */
    public function __invoke(Note $note): string
    {
        $note->loadMissing(['patient.doctor', 'patient.insuranceCompany', 'patient.homeHealthAgency', 'visit', 'author']);

        $pdf = Pdf::loadView($this->viewFor($note), [
            'note' => $note,
            'signatureDataUri' => $this->signatureDataUri($note->signature_path),
            'patientSignatureDataUri' => $this->signatureDataUri($note->patient_signature_path),
        ]);

        $path = sprintf('notes/%d/%s-%s.pdf', $note->patient_id, $note->type, $note->id);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Each wizard form prints on its own template; the clinical IE/RE/DC/FU
     * notes all share `pdf.notes.note`.
     */
    protected function viewFor(Note $note): string
    {
        return match ($note->type) {
            Note::TYPE_ROUTE_SHEET => 'pdf.notes.route-sheet',
            Note::TYPE_VISIT_NOTE => 'pdf.notes.visit-note',
            Note::TYPE_MISSED_VISIT => 'pdf.notes.missed-visit',
            Note::TYPE_COMMUNICATION => 'pdf.notes.communication',
            default => 'pdf.notes.note',
        };
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
