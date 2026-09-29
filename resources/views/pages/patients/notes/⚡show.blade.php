<?php

use App\Mail\NoteReadyForOffice;
use App\Models\Note;
use App\Models\NoteAddendum;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Note')] class extends Component
{
    public Note $note;

    public string $addendum = '';

    public function mount(Note $note): void
    {
        $this->note = $note->load(['patient', 'author', 'visit', 'addenda.author']);

        abort_unless($this->note->patient->isVisibleTo(auth()->user()), 403);
    }

    /**
     * Record a correction against an already-signed note. The original note
     * is never edited — see NoteAddendum's doc-block.
     */
    public function addAddendum(): void
    {
        abort_unless($this->note->isSigned(), 403);

        $this->validate([
            'addendum' => ['required', 'string'],
        ]);

        NoteAddendum::create([
            'note_id' => $this->note->id,
            'author_id' => auth()->id(),
            'content' => $this->addendum,
        ]);

        $this->reset('addendum');
        $this->note->load('addenda.author');

        Flux::toast(variant: 'success', text: __('Addendum added.'));
    }

    public function resend(): void
    {
        $officeEmail = $this->note->emailed_to ?? config('notes.office_email');

        Mail::to($officeEmail)->send(new NoteReadyForOffice($this->note));

        $this->note->forceFill([
            'emailed_to' => $officeEmail,
            'emailed_at' => now(),
        ])->save();

        Flux::toast(variant: 'success', text: __('Note re-sent to the office.'));
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);
        abort_if($this->note->isSigned(), 403, 'Signed notes cannot be deleted. Add an addendum instead.');

        $patient = $this->note->patient;

        $this->note->delete();

        $this->redirect(route('patients.show', $patient), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $note->label() }}</flux:heading>
            <flux:subheading size="lg">
                <flux:link :href="route('patients.show', $note->patient)" wire:navigate>{{ $note->patient->name }}</flux:link>
            </flux:subheading>
        </div>

        <div class="flex items-center gap-2">
            @if ($note->isSigned())
                <flux:badge :color="$note->isEmailed() ? 'green' : 'amber'">
                    {{ $note->isEmailed() ? __('Emailed') : __('Signed') }}
                </flux:badge>
            @else
                <flux:badge color="zinc">{{ __('Draft') }}</flux:badge>
            @endif
        </div>
    </div>

    <div class="flex flex-wrap gap-2 mb-6">
        @if ($note->pdf_path)
            <flux:button icon="arrow-down-tray" :href="route('notes.download', $note)">{{ __('Download PDF') }}</flux:button>
        @endif

        @if ($note->isSigned())
            <flux:button icon="envelope" wire:click="resend" wire:confirm="{{ __('Resend this note to the office?') }}">
                {{ __('Resend to office') }}
            </flux:button>
        @endif

        @can('notes.manage')
            @unless ($note->isSigned())
                <flux:button icon="trash" variant="danger" wire:click="delete" wire:confirm="{{ __('Delete this note? This cannot be undone.') }}">
                    {{ __('Delete') }}
                </flux:button>
            @endunless
        @endcan
    </div>

    <div class="max-w-2xl space-y-6">
        @if (in_array($note->type, Note::wizardTypes(), true))
            @foreach ($note->data ?? [] as $key => $value)
                <div>
                    <flux:heading size="sm">{{ Str::headline($key) }}</flux:heading>
                    <flux:text class="whitespace-pre-wrap">
                        @if (is_array($value))
                            {{ collect($value)->flatten()->filter()->implode(', ') ?: '—' }}
                        @else
                            {{ filled($value) ? $value : '—' }}
                        @endif
                    </flux:text>
                </div>
            @endforeach
        @else
            @foreach (Note::dataFieldLabels() as $key => $label)
                <div>
                    <flux:heading size="sm">{{ $label }}</flux:heading>
                    <flux:text class="whitespace-pre-wrap">{{ $note->data[$key] ?? '—' }}</flux:text>
                </div>
            @endforeach
        @endif

        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Staff Signature') }}</flux:heading>
            @if ($note->signature_path)
                <img
                    src="{{ route('notes.signature', $note) }}"
                    alt="{{ __('Staff signature') }}"
                    class="mt-2 h-20 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700"
                >
                <flux:text class="mt-1">
                    {{ __('Signed by :name on :date', ['name' => $note->author?->name, 'date' => optional($note->signed_at)->format('M j, Y g:i A')]) }}
                </flux:text>
            @else
                <flux:text class="mt-1">{{ __('Not signed.') }}</flux:text>
            @endif
        </div>

        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Patient Signature') }}</flux:heading>
            @if ($note->patient_signature_path)
                <img
                    src="{{ route('notes.patient-signature', $note) }}"
                    alt="{{ __('Patient signature') }}"
                    class="mt-2 h-20 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700"
                >
                <flux:text class="mt-1">
                    {{ __('Signed by :name on :date', ['name' => $note->patient->name, 'date' => optional($note->patient_signed_at)->format('M j, Y g:i A')]) }}
                </flux:text>
            @else
                <flux:text class="mt-1">{{ __('Not signed.') }}</flux:text>
            @endif
        </div>

        @if ($note->isSigned())
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:heading size="sm">{{ __('Addenda') }}</flux:heading>
                <flux:text class="mt-1" variant="subtle">
                    {{ __('Signed notes cannot be edited. Use an addendum to record a correction or additional information — the original stays as signed.') }}
                </flux:text>

                @forelse ($note->addenda as $addendum)
                    <div class="mt-3 rounded-md border border-zinc-200 p-3 dark:border-zinc-700">
                        <flux:text class="whitespace-pre-wrap">{{ $addendum->content }}</flux:text>
                        <flux:text class="mt-1 text-xs" variant="subtle">
                            {{ __('Added by :name on :date', ['name' => $addendum->author?->name ?? __('Deleted user'), 'date' => $addendum->created_at->format('M j, Y g:i A')]) }}
                        </flux:text>
                    </div>
                @empty
                    <flux:text class="mt-2" variant="subtle">{{ __('No addenda yet.') }}</flux:text>
                @endforelse

                <form wire:submit="addAddendum" class="mt-4 space-y-3">
                    <flux:textarea wire:model="addendum" :label="__('Add an addendum')" rows="3" />
                    <div class="flex justify-end">
                        <flux:button type="submit" icon="plus">{{ __('Add addendum') }}</flux:button>
                    </div>
                </form>
            </div>
        @endif
    </div>
</section>
