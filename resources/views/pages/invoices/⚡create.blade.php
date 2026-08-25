<?php

use App\Actions\Invoices\GenerateInvoicePdf;
use App\Models\HomeHealthAgency;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('New Invoice')] class extends Component {
    public ?int $home_health_agency_id = null;

    public string $weekOf = '';

    /** @var array<int, int> */
    public array $selectedVisitIds = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        $this->weekOf = now()->toDateString();
    }

    public function updatedHomeHealthAgencyId(): void
    {
        $this->selectedVisitIds = [];
    }

    public function updatedWeekOf(): void
    {
        $this->selectedVisitIds = [];
    }

    #[Computed]
    public function weekStart(): Carbon
    {
        return Carbon::parse($this->weekOf ?: now())->startOfWeek(Carbon::MONDAY);
    }

    #[Computed]
    public function weekEnd(): Carbon
    {
        return $this->weekStart->copy()->endOfWeek(Carbon::SUNDAY);
    }

    /**
     * @return Collection<int, HomeHealthAgency>
     */
    #[Computed]
    public function homeHealthAgencies(): Collection
    {
        return HomeHealthAgency::orderBy('name')->get();
    }

    /**
     * The logged-in therapist's own visits for the selected agency's patients
     * during the selected week, that aren't already on another invoice.
     *
     * @return Collection<int, Visit>
     */
    #[Computed]
    public function eligibleVisits(): Collection
    {
        if (! $this->home_health_agency_id) {
            return collect();
        }

        return Visit::query()
            ->where('therapist_id', auth()->id())
            ->whereHas('patient', fn ($query) => $query->where('home_health_agency_id', $this->home_health_agency_id))
            ->whereBetween('scheduled_at', [$this->weekStart, $this->weekEnd])
            ->whereDoesntHave('invoiceItem')
            ->with('patient')
            ->orderBy('scheduled_at')
            ->get();
    }

    #[Computed]
    public function total(): float
    {
        $rates = config('billing.visit_rates');

        return $this->eligibleVisits
            ->whereIn('id', $this->selectedVisitIds)
            ->sum(fn (Visit $visit) => $rates[$visit->visit_type] ?? 0);
    }

    public function createInvoice(): void
    {
        abort_unless(auth()->user()->can('invoices.manage'), 403);

        $validated = $this->validate([
            'home_health_agency_id' => ['required', 'exists:home_health_agencies,id'],
            'selectedVisitIds' => ['required', 'array', 'min:1'],
        ]);

        // Only allow visits that are actually eligible right now, ignoring any client-side tampering.
        $eligibleIds = $this->eligibleVisits->pluck('id');
        $visitIds = $eligibleIds->intersect($validated['selectedVisitIds'])->values();

        if ($visitIds->isEmpty()) {
            $this->addError('selectedVisitIds', __('Select at least one visit.'));

            return;
        }

        $rates = config('billing.visit_rates');
        $visits = $this->eligibleVisits->whereIn('id', $visitIds);

        $invoice = DB::transaction(function () use ($visits, $rates) {
            $invoice = Invoice::create([
                'home_health_agency_id' => $this->home_health_agency_id,
                'created_by' => auth()->id(),
                'invoice_number' => 'PENDING',
                'week_start_date' => $this->weekStart,
                'week_end_date' => $this->weekEnd,
                'status' => Invoice::STATUS_DRAFT,
            ]);

            $total = 0;

            foreach ($visits as $visit) {
                $rate = $rates[$visit->visit_type] ?? 0;
                $total += $rate;

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'visit_id' => $visit->id,
                    'rate_amount' => $rate,
                ]);
            }

            $invoice->update([
                'invoice_number' => sprintf('INV-%s-%04d', $this->weekStart->format('Ymd'), $invoice->id),
                'total_amount' => $total,
            ]);

            return $invoice;
        });

        $invoice->update(['pdf_path' => (new GenerateInvoicePdf)($invoice)]);

        $this->redirect(route('invoices.show', $invoice), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('New invoice') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Bill a home health agency for a week of visits') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <form wire:submit="createInvoice" class="space-y-6">
        <div class="flex flex-wrap gap-4">
            <flux:select wire:model.live="home_health_agency_id" :label="__('Home health agency')" :placeholder="__('Select agency')" class="max-w-sm">
                @foreach ($this->homeHealthAgencies as $hha)
                    <flux:select.option :value="$hha->id">{{ $hha->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model.live="weekOf" type="date" :label="__('Week of')" class="max-w-xs" />
        </div>

        <flux:text class="text-sm">
            {{ __('Billing week') }}: {{ $this->weekStart->format('M j, Y') }} – {{ $this->weekEnd->format('M j, Y') }}
        </flux:text>

        @error('selectedVisitIds')
            <flux:text class="text-sm text-red-600">{{ $message }}</flux:text>
        @enderror

        @if ($home_health_agency_id)
            <flux:table>
                <flux:table.columns>
                    <flux:table.column></flux:table.column>
                    <flux:table.column>{{ __('Patient') }}</flux:table.column>
                    <flux:table.column>{{ __('Visit type') }}</flux:table.column>
                    <flux:table.column>{{ __('Date') }}</flux:table.column>
                    <flux:table.column>{{ __('Rate') }}</flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($this->eligibleVisits as $visit)
                        <flux:table.row :key="$visit->id">
                            <flux:table.cell>
                                <flux:checkbox wire:model.live="selectedVisitIds" value="{{ $visit->id }}" />
                            </flux:table.cell>
                            <flux:table.cell>{{ $visit->patient->name }}</flux:table.cell>
                            <flux:table.cell>{{ strtoupper($visit->visit_type) }}</flux:table.cell>
                            <flux:table.cell>{{ $visit->scheduled_at->format('M j, Y') }}</flux:table.cell>
                            <flux:table.cell>${{ number_format(config('billing.visit_rates')[$visit->visit_type] ?? 0, 2) }}</flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell colspan="5">
                                <flux:text class="text-sm">{{ __('No unbilled visits for this agency during this week.') }}</flux:text>
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>

            <div class="flex items-center justify-end gap-4">
                <flux:heading size="lg">{{ __('Total') }}: ${{ number_format($this->total, 2) }}</flux:heading>
                <flux:button type="submit" variant="primary">{{ __('Create invoice') }}</flux:button>
            </div>
        @endif
    </form>
</section>
