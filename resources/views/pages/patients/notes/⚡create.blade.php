<?php

use App\Actions\Notes\GenerateNotePdf;
use App\Mail\NoteReadyForOffice;
use App\Models\Note;
use App\Models\Patient;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Note')] class extends Component {
    public Patient $patient;

    public string $type = '';

    /** @var array<string, string> */
    public array $fields = [];

    public ?string $signature = null;

    public function mount(Patient $patient, string $type): void
    {
        abort_unless(in_array($type, Note::types(), true), 404);

        $this->patient = $patient;
        $this->type = $type;

        $this->fields = collect(Note::dataFieldLabels())
            ->keys()
            ->mapWithKeys(fn (string $key) => [$key => ''])
            ->all();

        $this->prefill();
    }

    /**
     * Carry forward data from earlier notes, per the documented workflow:
     * RE copies from IE, DC copies from IE+RE, FU copies from the previous note.
     */
    protected function prefill(): void
    {
        $latestOfType = fn (string $type) => $this->patient->notes()->where('type', $type)->latest()->first();

        $source = match ($this->type) {
            Note::TYPE_RE => $latestOfType(Note::TYPE_IE)?->data,
            Note::TYPE_DC => array_merge(
                $latestOfType(Note::TYPE_IE)?->data ?? [],
                $latestOfType(Note::TYPE_RE)?->data ?? [],
            ),
            Note::TYPE_FU => $this->patient->notes()->latest()->first()?->data,
            default => null,
        };

        if ($source) {
            $this->fields = array_merge($this->fields, array_intersect_key($source, $this->fields));
        }
    }

    public function signAndSend(): void
    {
        $this->validate([
            'fields.assessment' => ['required', 'string'],
            'fields.plan' => ['required', 'string'],
        ]);

        if (blank($this->signature)) {
            $this->addError('signature', __('Please sign before submitting.'));

            return;
        }

        $note = Note::create([
            'patient_id' => $this->patient->id,
            'author_id' => auth()->id(),
            'type' => $this->type,
            'data' => $this->fields,
        ]);

        [, $base64] = explode(',', $this->signature, 2);

        $signaturePath = "signatures/{$note->id}.png";
        Storage::disk('local')->put($signaturePath, base64_decode($base64));

        $note->forceFill([
            'signature_path' => $signaturePath,
            'signed_at' => now(),
        ])->save();

        $note->forceFill([
            'pdf_path' => (new GenerateNotePdf)($note),
        ])->save();

        $officeEmail = config('notes.office_email');

        Mail::to($officeEmail)->send(new NoteReadyForOffice($note));

        $note->forceFill([
            'emailed_to' => $officeEmail,
            'emailed_at' => now(),
        ])->save();

        Flux::toast(variant: 'success', text: __('Note signed and emailed to the office.'));

        $this->redirect(route('notes.show', $note), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __(':type Note', ['type' => strtoupper($type)]) }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ $patient->name }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <form wire:submit="signAndSend" class="max-w-2xl space-y-6">
        <flux:textarea wire:model="fields.subjective" :label="__('Subjective / Chief Complaint')" rows="3" />

        <div class="grid grid-cols-3 gap-4">
            <flux:input wire:model="fields.blood_pressure" :label="__('Blood Pressure')" />
            <flux:input wire:model="fields.heart_rate" :label="__('Heart Rate')" />
            <flux:input wire:model="fields.pain_level" :label="__('Pain Level (0-10)')" />
        </div>

        <flux:textarea wire:model="fields.objective_findings" :label="__('Objective Findings')" rows="4" />
        <flux:textarea wire:model="fields.assessment" :label="__('Assessment')" rows="4" required />
        <flux:textarea wire:model="fields.goals" :label="__('Goals')" rows="3" />
        <flux:textarea wire:model="fields.plan" :label="__('Plan')" rows="3" required />

        <flux:input wire:model="fields.visit_duration_minutes" type="number" :label="__('Visit Duration (minutes)')" class="max-w-xs" />

        <flux:textarea wire:model="fields.therapist_notes" :label="__('Additional Therapist Notes')" rows="3" />

        <div>
            <flux:label>{{ __('Signature') }}</flux:label>
            <x-signature-pad model="signature" class="mt-2" />
            <flux:error name="signature" />
        </div>

        <div class="flex justify-end">
            <flux:button type="submit" variant="primary" icon="paper-airplane">
                {{ __('Sign & send to office') }}
            </flux:button>
        </div>
    </form>
</section>
