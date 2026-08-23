<?php

use App\Mail\NoteReadyForOffice;
use App\Models\Note;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Note')] class extends Component
{
    public Note $note;

    public function mount(Note $note): void
    {
        $this->note = $note->load(['patient', 'author', 'visit']);
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
            <flux:button icon="trash" variant="danger" wire:click="delete" wire:confirm="{{ __('Delete this note? This cannot be undone.') }}">
                {{ __('Delete') }}
            </flux:button>
        @endcan
    </div>

    <div class="max-w-2xl space-y-6">
        @foreach (Note::dataFieldLabels() as $key => $label)
            <div>
                <flux:heading size="sm">{{ $label }}</flux:heading>
                <flux:text class="whitespace-pre-wrap">{{ $note->data[$key] ?? '—' }}</flux:text>
            </div>
        @endforeach

        <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
            <flux:heading size="sm">{{ __('Signature') }}</flux:heading>
            @if ($note->signature_path)
                <img
                    src="{{ route('notes.signature', $note) }}"
                    alt="{{ __('Signature') }}"
                    class="mt-2 h-20 rounded-md border border-zinc-200 bg-white p-2 dark:border-zinc-700"
                >
                <flux:text class="mt-1">
                    {{ __('Signed by :name on :date', ['name' => $note->author?->name, 'date' => optional($note->signed_at)->format('M j, Y g:i A')]) }}
                </flux:text>
            @else
                <flux:text class="mt-1">{{ __('Not signed.') }}</flux:text>
            @endif
        </div>
    </div>
</section>
