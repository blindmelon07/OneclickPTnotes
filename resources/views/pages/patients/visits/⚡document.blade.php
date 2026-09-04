<?php

use App\Actions\Notes\GenerateNotePdf;
use App\Models\Note;
use App\Models\Patient;
use App\Models\Visit;
use Flux\Flux;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Visit Documentation')] class extends Component {
    /**
     * Signature properties the wizard is allowed to clear — `clearSignature()`
     * is a public Livewire action, so the name it takes is whitelisted.
     *
     * @var array<int, string>
     */
    public const SIGNATURE_MODELS = [
        'routeSheetSignature',
        'routeSheetStaffSignature',
        'visitNoteStaffSignature',
        'missedVisitStaffSignature',
        'communicationStaffSignature',
    ];

    public Patient $patient;

    public Visit $visit;

    public int $step = 1;

    /**
     * Whether each optional step is being filled in.
     *
     * @var array<string, bool>
     */
    public array $include = [
        Note::TYPE_MISSED_VISIT => false,
        Note::TYPE_COMMUNICATION => false,
    ];

    /** @var array<string, mixed> */
    public array $routeSheet = [];

    /** @var array<string, mixed> */
    public array $visitNote = [];

    /** @var array<string, mixed> */
    public array $missedVisit = [];

    /** @var array<string, mixed> */
    public array $communication = [];

    /** Data URI captured from the route sheet's patient signature pad. */
    public ?string $routeSheetSignature = null;

    /** Data URI captured from the route sheet's PT Assistant signature pad. */
    public ?string $routeSheetStaffSignature = null;

    /** Data URI captured from the visit note's PT Assistant signature pad. */
    public ?string $visitNoteStaffSignature = null;

    /** Data URI captured from the missed visit report's signature pad. */
    public ?string $missedVisitStaffSignature = null;

    /** Data URI captured from the communication note's signature pad. */
    public ?string $communicationStaffSignature = null;

    public function mount(Patient $patient, Visit $visit): void
    {
        abort_unless($patient->isVisibleTo(auth()->user()), 403);
        abort_unless($visit->patient_id === $patient->id, 404);

        // The patient page swaps Proceed for a "Notes done" badge once a visit
        // is documented; close the URL behind it so the forms can't be filed twice.
        abort_if($visit->notes()->exists(), 403);

        $this->patient = $patient;
        $this->visit = $visit;

        $visitDate = $visit->scheduled_at->toDateString();
        $agency = $patient->homeHealthAgency?->name ?? '';

        $this->routeSheet = [
            'agency_name' => $agency,
            'patient_name' => $patient->name,
            'mr_number' => '',
            'week_of' => $visit->scheduled_at->startOfWeek()->toDateString(),
            'week_thru' => $visit->scheduled_at->endOfWeek()->toDateString(),
            'visit_code' => 'G0-157',
            'visit_date' => $visitDate,
            'time_in' => '',
            'time_out' => '',
            'comments' => '',
        ];

        $this->visitNote = [
            'agency_name' => $agency,
            'patient_name' => $patient->name,
            'mr_number' => '',
            'bp' => '',
            'pr' => '',
            'rr' => '',
            'pain' => '',
            'homebound_statuses' => [],
            'homebound_other' => '',
            'services_provided' => [],
            'services_other' => '',
            'observations' => '',
            'progress_towards_goal' => '',
            'date' => $visitDate,
        ];

        $this->missedVisit = [
            'date' => $visitDate,
            'agency_name' => $agency,
            'patient_name' => $patient->name,
            'mr_number' => '',
            'reasons' => [],
            'reason_other' => '',
            'notified_by_fax' => false,
            'notified_by_phone' => false,
            'spoke_with' => '',
            'rescheduled' => '',
            'next_visit_date' => '',
            'not_rescheduled_reason' => '',
            'comments' => '',
        ];

        $this->communication = [
            'date' => $visitDate,
            'agency_name' => $agency,
            'mr_number' => '',
            'patient_name' => $patient->name,
            'message' => '',
            'verbal_orders_name' => '',
            'verbal_orders_date' => '',
            'physician_signature_date' => '',
        ];
    }

    /**
     * The wizard steps actually in play — the optional ones drop out of the
     * count and the progress bar the moment they are toggled off.
     *
     * @return array<int, string>
     */
    #[Computed]
    public function activeSteps(): array
    {
        return collect(Note::wizardTypes())
            ->reject(fn (string $type) => in_array($type, Note::optionalWizardTypes(), true) && ! $this->include[$type])
            ->values()
            ->all();
    }

    public function currentType(): string
    {
        return $this->activeSteps[$this->step - 1] ?? Note::TYPE_ROUTE_SHEET;
    }

    public function isLastStep(): bool
    {
        return $this->step >= count($this->activeSteps);
    }

    public function nextStep(): void
    {
        $this->validateStep($this->currentType());

        $this->step = min($this->step + 1, count($this->activeSteps));
    }

    public function previousStep(): void
    {
        $this->step = max($this->step - 1, 1);
    }

    /**
     * The agency name and MR # are written once on the route sheet and carried
     * onto every other form, which all print the same values.
     *
     * @var array<int, string>
     */
    public const CARRIED_ROUTE_SHEET_FIELDS = ['agency_name', 'mr_number'];

    public function updatedRouteSheet(mixed $value, string $key): void
    {
        if (! in_array($key, self::CARRIED_ROUTE_SHEET_FIELDS, true)) {
            return;
        }

        $this->visitNote[$key] = $value;
        $this->missedVisit[$key] = $value;
        $this->communication[$key] = $value;
    }

    /**
     * The assistant signs once on the route sheet; the later forms reuse that
     * signature unless one has already been drawn separately.
     */
    public function updatedRouteSheetStaffSignature(?string $value): void
    {
        foreach (['visitNoteStaffSignature', 'missedVisitStaffSignature', 'communicationStaffSignature'] as $model) {
            if (blank($this->{$model})) {
                $this->{$model} = $value;
            }
        }
    }

    /**
     * Discard a captured signature so its pad is offered again.
     */
    public function clearSignature(string $model): void
    {
        abort_unless(in_array($model, self::SIGNATURE_MODELS, true), 403);

        $this->{$model} = null;
    }

    /**
     * Toggling an optional form on or off changes the step list underneath the
     * user, so clamp the pointer back into range.
     */
    public function updatedInclude(): void
    {
        unset($this->activeSteps);

        $this->step = min($this->step, count($this->activeSteps));
    }

    protected function validateStep(string $type): void
    {
        match ($type) {
            Note::TYPE_ROUTE_SHEET => $this->validate([
                'routeSheet.agency_name' => ['required', 'string', 'max:255'],
                'routeSheet.week_of' => ['required', 'date'],
                'routeSheet.week_thru' => ['required', 'date', 'after_or_equal:routeSheet.week_of'],
                'routeSheet.visit_code' => ['required', 'in:'.implode(',', array_keys(Note::visitCodes()))],
                'routeSheet.visit_date' => ['required', 'date'],
                'routeSheetSignature' => ['required', 'string'],
                'routeSheetStaffSignature' => ['required', 'string'],
            ]),
            Note::TYPE_VISIT_NOTE => $this->validate([
                'visitNote.agency_name' => ['required', 'string', 'max:255'],
                'visitNote.date' => ['required', 'date'],
                'visitNote.observations' => ['required', 'string'],
                'visitNoteStaffSignature' => ['required', 'string'],
            ]),
            Note::TYPE_MISSED_VISIT => $this->validate([
                'missedVisit.date' => ['required', 'date'],
                'missedVisit.reasons' => ['required_without:missedVisit.reason_other', 'array'],
                'missedVisit.rescheduled' => ['required', 'in:yes,no'],
                'missedVisitStaffSignature' => ['required', 'string'],
            ]),
            Note::TYPE_COMMUNICATION => $this->validate([
                'communication.date' => ['required', 'date'],
                'communication.message' => ['required', 'string'],
                'communicationStaffSignature' => ['required', 'string'],
            ]),
            default => null,
        };
    }

    /**
     * Store the drawn signatures alongside the note the same way a signed
     * clinical note does, so they print on the PDF and are served by the
     * `notes.signature` routes rather than living in the data JSON. Only the
     * route sheet carries a patient signature; every signed form gets the
     * supervising therapist's stamp.
     */
    protected function storeSignatures(Note $note, string $staffSignature, ?string $patientSignature = null): void
    {
        $now = now();

        $attributes = [
            'signature_path' => $this->storeSignature($staffSignature, "signatures/{$note->id}.png"),
            'signed_at' => $now,
            'countersigned_at' => Note::countersignaturePath() ? $now : null,
        ];

        if ($patientSignature) {
            $attributes['patient_signature_path'] = $this->storeSignature($patientSignature, "signatures/{$note->id}-patient.png");
            $attributes['patient_signed_at'] = $now;
        }

        $note->forceFill($attributes)->save();
    }

    /**
     * Decode one signature-pad data URI to a stored PNG.
     */
    protected function storeSignature(string $dataUri, string $path): string
    {
        [, $base64] = explode(',', $dataUri, 2);

        Storage::disk('local')->put($path, base64_decode($base64));

        return $path;
    }

    /**
     * Persist one Note per completed form, each with its own stored PDF.
     */
    public function submit(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        foreach ($this->activeSteps as $index => $type) {
            try {
                $this->validateStep($type);
            } catch (ValidationException $exception) {
                // Send the user to the step that failed — its fields are on a
                // page they may not be looking at.
                $this->step = $index + 1;

                throw $exception;
            }
        }

        $data = [
            Note::TYPE_ROUTE_SHEET => $this->routeSheet,
            Note::TYPE_VISIT_NOTE => $this->visitNote,
            Note::TYPE_MISSED_VISIT => $this->missedVisit,
            Note::TYPE_COMMUNICATION => $this->communication,
        ];

        foreach ($this->activeSteps as $type) {
            $note = Note::create([
                'patient_id' => $this->patient->id,
                'visit_id' => $this->visit->id,
                'author_id' => auth()->id(),
                'type' => $type,
                'data' => $data[$type],
            ]);

            match ($type) {
                Note::TYPE_ROUTE_SHEET => $this->storeSignatures($note, $this->routeSheetStaffSignature, $this->routeSheetSignature),
                Note::TYPE_VISIT_NOTE => $this->storeSignatures($note, $this->visitNoteStaffSignature),
                Note::TYPE_MISSED_VISIT => $this->storeSignatures($note, $this->missedVisitStaffSignature),
                Note::TYPE_COMMUNICATION => $this->storeSignatures($note, $this->communicationStaffSignature),
                default => null,
            };

            $note->forceFill(['pdf_path' => (new GenerateNotePdf)($note)])->save();
        }

        Flux::toast(variant: 'success', text: __('Visit documentation saved.'));

        $this->redirect(route('patients.show', $this->patient), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Visit documentation') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ $patient->name }} — {{ $visit->scheduled_at->format('M j, Y g:i A') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-2">
        @foreach ($this->activeSteps as $index => $type)
            <flux:badge :color="$step === $index + 1 ? 'blue' : ($step > $index + 1 ? 'green' : 'zinc')" size="sm">
                {{ $index + 1 }}. {{ \App\Models\Note::labelFor($type) }}
            </flux:badge>
        @endforeach
    </div>

    <div class="mb-6 max-w-2xl space-y-2 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
        <flux:label>{{ __('Optional forms') }}</flux:label>
        <flux:field variant="inline">
            <flux:checkbox wire:model.live="include.missed_visit" />
            <flux:label>{{ __('Include a Missed Visit Report') }}</flux:label>
        </flux:field>
        <flux:field variant="inline">
            <flux:checkbox wire:model.live="include.communication" />
            <flux:label>{{ __('Include a Communication Note to the HHA') }}</flux:label>
        </flux:field>
    </div>

    <form wire:submit="submit" class="max-w-2xl space-y-6">
        @if ($this->currentType() === \App\Models\Note::TYPE_ROUTE_SHEET)
            @include('partials.visit-forms.route-sheet')
        @elseif ($this->currentType() === \App\Models\Note::TYPE_VISIT_NOTE)
            @include('partials.visit-forms.visit-note')
        @elseif ($this->currentType() === \App\Models\Note::TYPE_MISSED_VISIT)
            @include('partials.visit-forms.missed-visit')
        @else
            @include('partials.visit-forms.communication')
        @endif

        <div class="flex items-center gap-2">
            @if ($step > 1)
                <flux:button type="button" wire:click="previousStep">{{ __('Back') }}</flux:button>
            @endif

            <flux:spacer />

            @if ($this->isLastStep())
                <flux:button type="submit" variant="primary">{{ __('Finish and save') }}</flux:button>
            @else
                <flux:button type="button" variant="primary" wire:click="nextStep">{{ __('Next') }}</flux:button>
            @endif
        </div>
    </form>
</section>
