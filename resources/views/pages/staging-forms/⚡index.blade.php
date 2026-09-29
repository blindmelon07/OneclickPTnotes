<?php

use App\Models\StagingForm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Staging Forms')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function stagingForms()
    {
        return StagingForm::query()
            ->with('createdBy')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('patient_name', 'like', "%{$this->search}%")
                    ->orWhere('agency_name', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Staging Forms') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Home Health IE packet — fill in the evaluation once, and the other four forms copy from it') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search patient or agency')" class="max-w-64" />

        <flux:spacer />

        <flux:button variant="primary" icon="plus" :href="route('staging-forms.create')" wire:navigate>
            {{ __('New staging form') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->stagingForms">
        <flux:table.columns>
            <flux:table.column>{{ __('Patient') }}</flux:table.column>
            <flux:table.column>{{ __('Agency') }}</flux:table.column>
            <flux:table.column>{{ __('Prepared by') }}</flux:table.column>
            <flux:table.column>{{ __('Last updated') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->stagingForms as $stagingForm)
                <flux:table.row :key="$stagingForm->id">
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('staging-forms.show', $stagingForm)" wire:navigate>{{ $stagingForm->patient_name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $stagingForm->agency_name }}</flux:table.cell>
                    <flux:table.cell>{{ $stagingForm->createdBy?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $stagingForm->updated_at->format('M j, Y g:i A') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No staging forms yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
