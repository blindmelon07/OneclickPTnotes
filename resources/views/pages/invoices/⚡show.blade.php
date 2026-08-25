<?php

use App\Models\Invoice;
use Flux\Flux;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Invoice')] class extends Component {
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        $this->invoice = $invoice->load(['homeHealthAgency', 'createdBy', 'items.visit.patient']);
    }

    public function markAsSent(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        if ($this->invoice->status !== Invoice::STATUS_DRAFT) {
            return;
        }

        $this->invoice->update(['status' => Invoice::STATUS_SENT, 'sent_at' => now()]);

        Flux::toast(variant: 'success', text: __('Invoice marked as sent.'));
    }

    public function markAsPaid(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        if ($this->invoice->status !== Invoice::STATUS_SENT) {
            return;
        }

        $this->invoice->update(['status' => Invoice::STATUS_PAID, 'paid_at' => now()]);

        Flux::toast(variant: 'success', text: __('Invoice marked as paid.'));
    }

    public function delete(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        if (! $this->invoice->isDraft()) {
            Flux::toast(variant: 'danger', text: __('Only draft invoices can be deleted.'));

            return;
        }

        $this->invoice->delete();

        $this->redirect(route('invoices.index'), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <div class="flex items-center justify-between">
            <div>
                <flux:heading size="xl" level="1">{{ $invoice->invoice_number }}</flux:heading>
                <flux:subheading size="lg">{{ $invoice->homeHealthAgency->name }}</flux:subheading>
            </div>
            <flux:badge :color="$invoice->statusColor()" size="lg">{{ ucfirst($invoice->status) }}</flux:badge>
        </div>
        <flux:separator variant="subtle" class="mt-6" />
    </div>

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
            <flux:text class="text-sm">{{ __('Week') }}</flux:text>
            <flux:heading>{{ $invoice->week_start_date->format('M j') }} – {{ $invoice->week_end_date->format('M j, Y') }}</flux:heading>
        </div>
        <div>
            <flux:text class="text-sm">{{ __('Prepared by') }}</flux:text>
            <flux:heading>{{ $invoice->createdBy?->name }}</flux:heading>
        </div>
        <div>
            <flux:text class="text-sm">{{ __('Total') }}</flux:text>
            <flux:heading>${{ number_format((float) $invoice->total_amount, 2) }}</flux:heading>
        </div>
        <div>
            <flux:text class="text-sm">{{ __('PDF') }}</flux:text>
            @if ($invoice->pdf_path)
                <flux:link :href="route('invoices.download', $invoice)">{{ __('Download') }}</flux:link>
            @else
                <flux:text>{{ __('Not available') }}</flux:text>
            @endif
        </div>
    </div>

    <flux:table>
        <flux:table.columns>
            <flux:table.column>{{ __('Patient') }}</flux:table.column>
            <flux:table.column>{{ __('Visit type') }}</flux:table.column>
            <flux:table.column>{{ __('Date') }}</flux:table.column>
            <flux:table.column>{{ __('Amount') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($invoice->items as $item)
                <flux:table.row :key="$item->id">
                    <flux:table.cell>{{ $item->visit->patient->name }}</flux:table.cell>
                    <flux:table.cell>{{ strtoupper($item->visit->visit_type) }}</flux:table.cell>
                    <flux:table.cell>{{ $item->visit->scheduled_at->format('M j, Y') }}</flux:table.cell>
                    <flux:table.cell>${{ number_format((float) $item->rate_amount, 2) }}</flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <div class="mt-6 flex justify-end gap-2">
        @if ($invoice->status === Invoice::STATUS_DRAFT)
            <flux:button variant="ghost" wire:click="delete" wire:confirm="{{ __('Delete this invoice?') }}">
                {{ __('Delete') }}
            </flux:button>
            <flux:button variant="primary" wire:click="markAsSent">{{ __('Mark as sent') }}</flux:button>
        @elseif ($invoice->status === Invoice::STATUS_SENT)
            <flux:button variant="primary" wire:click="markAsPaid">{{ __('Mark as paid') }}</flux:button>
        @endif
    </div>
</section>
