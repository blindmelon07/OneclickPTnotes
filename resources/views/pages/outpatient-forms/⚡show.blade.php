<?php

use App\Models\OutpatientForm;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Outpatient Form')] class extends Component {
    public OutpatientForm $outpatientForm;

    public string $document = OutpatientForm::DOCUMENT_INITIAL_EVALUATION;

    public function mount(OutpatientForm $outpatientForm): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->outpatientForm = $outpatientForm->load('createdBy');
    }

    public function updatedDocument(): void
    {
        if (! array_key_exists($this->document, OutpatientForm::documents())) {
            $this->document = OutpatientForm::DOCUMENT_INITIAL_EVALUATION;
        }
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->outpatientForm->delete();

        $this->redirect(route('outpatient-forms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ $outpatientForm->patient_name }}</flux:heading>
                <flux:subheading size="lg">{{ $outpatientForm->diagnosis }}</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" wire:click="delete" wire:confirm="{{ __('Delete this outpatient form?') }}">
                    {{ __('Delete') }}
                </flux:button>
                <flux:button icon="pencil-square" :href="route('outpatient-forms.edit', $outpatientForm)" wire:navigate>
                    {{ __('Edit forms') }}
                </flux:button>
                <flux:button variant="primary" icon="arrow-down-tray" :href="route('outpatient-forms.pdf', [$outpatientForm, OutpatientForm::DOCUMENT_ALL, 'download' => 1])">
                    {{ __('Download all 3') }}
                </flux:button>
            </div>
        </div>
        <flux:text class="mt-2 text-sm">
            {{ __('Prepared by :name, last updated :date', ['name' => $outpatientForm->createdBy?->name ?? '—', 'date' => $outpatientForm->updated_at->format('M j, Y g:i A')]) }}
        </flux:text>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <div class="space-y-1">
            @foreach (OutpatientForm::documents() as $key => $label)
                <div @class([
                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2',
                    'bg-zinc-100 dark:bg-zinc-700' => $document === $key,
                ])>
                    <button type="button" wire:click="$set('document', '{{ $key }}')" class="flex-1 text-start text-sm font-medium">
                        {{ $label }}
                    </button>
                    <flux:button size="xs" variant="ghost" icon="arrow-down-tray" :href="route('outpatient-forms.pdf', [$outpatientForm, $key, 'download' => 1])" :aria-label="__('Download')" />
                </div>
            @endforeach
        </div>

        <iframe
            wire:key="preview-{{ $document }}-{{ $outpatientForm->updated_at->timestamp }}"
            src="{{ route('outpatient-forms.pdf', [$outpatientForm, $document]) }}"
            title="{{ OutpatientForm::documents()[$document] }}"
            class="h-[80vh] w-full rounded-xl border border-zinc-200 bg-white dark:border-zinc-700"
        ></iframe>
    </div>
</section>
