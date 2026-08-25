<?php

use App\Models\Invoice;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Invoices')] class extends Component {
    use WithPagination;

    public string $statusFilter = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function invoices()
    {
        return Invoice::query()
            ->with('homeHealthAgency')
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderByDesc('week_start_date')
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Invoices') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Weekly invoices billed to home health agencies') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (Invoice::statuses() as $statusOption)
                <flux:select.option :value="$statusOption">{{ ucfirst($statusOption) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:spacer />

        <flux:button variant="primary" icon="plus" :href="route('invoices.create')" wire:navigate>
            {{ __('New invoice') }}
        </flux:button>
    </div>

    <flux:table :paginate="$this->invoices">
        <flux:table.columns>
            <flux:table.column>{{ __('Invoice #') }}</flux:table.column>
            <flux:table.column>{{ __('HHA') }}</flux:table.column>
            <flux:table.column>{{ __('Week') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
            <flux:table.column>{{ __('Total') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->invoices as $invoice)
                <flux:table.row :key="$invoice->id">
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('invoices.show', $invoice)" wire:navigate>{{ $invoice->invoice_number }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $invoice->homeHealthAgency->name }}</flux:table.cell>
                    <flux:table.cell>{{ $invoice->week_start_date->format('M j') }} – {{ $invoice->week_end_date->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$invoice->statusColor()" size="sm">{{ ucfirst($invoice->status) }}</flux:badge>
                    </flux:table.cell>
                    <flux:table.cell>${{ number_format((float) $invoice->total_amount, 2) }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>
</section>
