<?php

use App\Models\OutpatientForm;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Outpatient Forms')] class extends Component {
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
    public function outpatientForms()
    {
        return OutpatientForm::query()
            ->with('createdBy')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('patient_name', 'like', "%{$this->search}%")
                    ->orWhere('diagnosis', 'like', "%{$this->search}%");
            }))
            ->latest()
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Outpatient Forms') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Initial Evaluation, Re-Evaluation and Discharge Summary — fill in the IE once, and the RE and DC baselines copy from it') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search patient or diagnosis')" class="max-w-64" />

        <flux:spacer />

        <flux:button variant="primary" icon="plus" :href="route('outpatient-forms.create')" wire:navigate>
            {{ __('New outpatient form') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->outpatientForms">
        <flux:table.columns>
            <flux:table.column>{{ __('Patient') }}</flux:table.column>
            <flux:table.column>{{ __('Diagnosis') }}</flux:table.column>
            <flux:table.column>{{ __('Prepared by') }}</flux:table.column>
            <flux:table.column>{{ __('Last updated') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @forelse ($this->outpatientForms as $outpatientForm)
                <flux:table.row :key="$outpatientForm->id">
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('outpatient-forms.show', $outpatientForm)" wire:navigate>{{ $outpatientForm->patient_name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $outpatientForm->diagnosis }}</flux:table.cell>
                    <flux:table.cell>{{ $outpatientForm->createdBy?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $outpatientForm->updated_at->format('M j, Y g:i A') }}</flux:table.cell>
                </flux:table.row>
            @empty
                <flux:table.row>
                    <flux:table.cell colspan="4">{{ __('No outpatient forms yet.') }}</flux:table.cell>
                </flux:table.row>
            @endforelse
        </flux:table.rows>
    </flux:table>
</section>
