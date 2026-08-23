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

        $signatureDataUri = null;

        if ($note->signature_path && Storage::disk('local')->exists($note->signature_path)) {
            $signatureDataUri = 'data:image/png;base64,'.base64_encode(
                Storage::disk('local')->get($note->signature_path)
            );
        }

        $pdf = Pdf::loadView('pdf.notes.note', [
            'note' => $note,
            'signatureDataUri' => $signatureDataUri,
        ]);

        $path = sprintf('notes/%d/%s-%s.pdf', $note->patient_id, $note->type, $note->id);

        Storage::disk('local')->put($path, $pdf->output());

        return $path;
    }
}
