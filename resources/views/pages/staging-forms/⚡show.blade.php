<?php

use App\Models\StagingForm;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Staging Form')] class extends Component {
    public StagingForm $stagingForm;

    public string $document = StagingForm::DOCUMENT_EVALUATION;

    public function mount(StagingForm $stagingForm): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->stagingForm = $stagingForm->load('createdBy');
    }

    public function updatedDocument(): void
    {
        if (! array_key_exists($this->document, StagingForm::documents())) {
            $this->document = StagingForm::DOCUMENT_EVALUATION;
        }
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->stagingForm->delete();

        $this->redirect(route('staging-forms.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <flux:heading size="xl" level="1">{{ $stagingForm->patient_name }}</flux:heading>
                <flux:subheading size="lg">{{ $stagingForm->agency_name }}</flux:subheading>
            </div>
            <div class="flex gap-2">
                <flux:button variant="ghost" wire:click="delete" wire:confirm="{{ __('Delete this staging form?') }}">
                    {{ __('Delete') }}
                </flux:button>
                <flux:button icon="pencil-square" :href="route('staging-forms.edit', $stagingForm)" wire:navigate>
                    {{ __('Edit evaluation') }}
                </flux:button>
                <flux:button variant="primary" icon="arrow-down-tray" :href="route('staging-forms.pdf', [$stagingForm, StagingForm::DOCUMENT_ALL, 'download' => 1])">
                    {{ __('Download all 5') }}
                </flux:button>
            </div>
        </div>
        <flux:text class="mt-2 text-sm">
            {{ __('Prepared by :name, last updated :date', ['name' => $stagingForm->createdBy?->name ?? '—', 'date' => $stagingForm->updated_at->format('M j, Y g:i A')]) }}
        </flux:text>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="grid gap-6 lg:grid-cols-[16rem_1fr]">
        <div class="space-y-1">
            @foreach (StagingForm::documents() as $key => $label)
                <div @class([
                    'flex items-center justify-between gap-2 rounded-lg px-3 py-2',
                    'bg-zinc-100 dark:bg-zinc-700' => $document === $key,
                ])>
                    <button type="button" wire:click="$set('document', '{{ $key }}')" class="flex-1 text-start text-sm font-medium">
                        {{ $label }}
                    </button>
                    <flux:button size="xs" variant="ghost" icon="arrow-down-tray" :href="route('staging-forms.pdf', [$stagingForm, $key, 'download' => 1])" :aria-label="__('Download')" />
                </div>
            @endforeach
        </div>

        <iframe
            wire:key="preview-{{ $document }}-{{ $stagingForm->updated_at->timestamp }}"
            src="{{ route('staging-forms.pdf', [$stagingForm, $document]) }}"
            title="{{ StagingForm::documents()[$document] }}"
            class="h-[80vh] w-full rounded-xl border border-zinc-200 bg-white dark:border-zinc-700"
        ></iframe>
    </div>
</section>
