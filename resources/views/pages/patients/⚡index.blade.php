<?php

use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Patient;
use App\Models\User;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Patients')] class extends Component {
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public string $name = '';

    public string $phone = '';

    public string $diagnosis = '';

    public ?int $doctor_id = null;

    public ?int $insurance_company_id = null;

    public ?int $home_health_agency_id = null;

    public ?int $pt_assistant_id = null;

    public ?int $approved_visits = null;

    public string $status = Patient::STATUS_ACTIVE;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function createPatient(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'insurance_company_id' => ['nullable', 'exists:insurance_companies,id'],
            'home_health_agency_id' => ['nullable', 'exists:home_health_agencies,id'],
            'pt_assistant_id' => ['nullable', 'exists:users,id'],
            'approved_visits' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'in:'.implode(',', Patient::statuses())],
        ]);

        if (auth()->user()->isRestrictedToAssignedPatients()) {
            $validated['pt_assistant_id'] = auth()->id();
        }

        $patient = Patient::create($validated);

        $this->reset(['name', 'phone', 'diagnosis', 'doctor_id', 'insurance_company_id', 'home_health_agency_id', 'pt_assistant_id', 'approved_visits']);
        $this->status = Patient::STATUS_ACTIVE;

        Flux::modal('create-patient')->close();

        $this->redirect(route('patients.show', $patient), navigate: true);
    }

    /**
     * @return Collection<int, Doctor>
     */
    #[Computed]
    public function doctors(): Collection
    {
        return Doctor::orderBy('name')->get();
    }

    /**
     * @return Collection<int, InsuranceCompany>
     */
    #[Computed]
    public function insuranceCompanies(): Collection
    {
        return InsuranceCompany::orderBy('name')->get();
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
     * @return Collection<int, User>
     */
    #[Computed]
    public function ptAssistants(): Collection
    {
        return User::havingRole(User::ROLE_PT_ASSISTANT)->orderBy('name')->get();
    }

    #[Computed]
    public function patients()
    {
        return Patient::query()
            ->visibleTo(auth()->user())
            ->with(['homeHealthAgency', 'ptAssistant'])
            ->when($this->search, fn ($query) => $query->where('name', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('name')
            ->paginate(10);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Patients') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('All patients on service') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <flux:input wire:model.live.debounce.300ms="search" :placeholder="__('Search patients...')" icon="magnifying-glass" class="max-w-xs" />

        <flux:select wire:model.live="statusFilter" :placeholder="__('All statuses')" class="max-w-48">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach (Patient::statusLabels() as $statusOption => $statusLabel)
                <flux:select.option :value="$statusOption">{{ __($statusLabel) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:spacer />

        @can('patients.manage')
            <flux:modal.trigger name="create-patient">
                <flux:button variant="primary" icon="plus">{{ __('New patient') }}</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <flux:table :paginate="$this->patients">
        <flux:table.columns>
            <flux:table.column>{{ __('Name') }}</flux:table.column>
            <flux:table.column>{{ __('Diagnosis') }}</flux:table.column>
            <flux:table.column>{{ __('HHA') }}</flux:table.column>
            <flux:table.column>{{ __('PT Assistant') }}</flux:table.column>
            <flux:table.column>{{ __('Status') }}</flux:table.column>
        </flux:table.columns>

        <flux:table.rows>
            @foreach ($this->patients as $patient)
                <flux:table.row :key="$patient->id">
                    <flux:table.cell variant="strong">
                        <flux:link :href="route('patients.show', $patient)" wire:navigate>{{ $patient->name }}</flux:link>
                    </flux:table.cell>
                    <flux:table.cell>{{ $patient->diagnosis }}</flux:table.cell>
                    <flux:table.cell>{{ $patient->homeHealthAgency?->name }}</flux:table.cell>
                    <flux:table.cell>{{ $patient->ptAssistant?->name ?: '—' }}</flux:table.cell>
                    <flux:table.cell>
                        <flux:badge :color="$patient->statusColor()" size="sm">{{ $patient->statusLabel() }}</flux:badge>
                    </flux:table.cell>
                </flux:table.row>
            @endforeach
        </flux:table.rows>
    </flux:table>

    <flux:modal name="create-patient" class="md:w-[28rem]">
        <form wire:submit="createPatient" class="space-y-4">
            <flux:heading size="lg">{{ __('New patient') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required autofocus />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:input wire:model="diagnosis" :label="__('Diagnosis')" />

            <flux:select wire:model.live="doctor_id" :label="__('Doctor')" :placeholder="__('Select doctor')">
                @foreach ($this->doctors as $doctor)
                    <flux:select.option :value="$doctor->id">{{ $doctor->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="insurance_company_id" :label="__('Insurance')" :placeholder="__('Select insurance')">
                @foreach ($this->insuranceCompanies as $insurance)
                    <flux:select.option :value="$insurance->id">{{ $insurance->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="home_health_agency_id" :label="__('HHA')" :placeholder="__('Select HHA')">
                @foreach ($this->homeHealthAgencies as $hha)
                    <flux:select.option :value="$hha->id">{{ $hha->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="pt_assistant_id" :label="__('PT Assistant')" :placeholder="__('Select PT Assistant')">
                @foreach ($this->ptAssistants as $ptAssistant)
                    <flux:select.option :value="$ptAssistant->id">{{ $ptAssistant->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input
                wire:model="approved_visits"
                type="number"
                min="1"
                :label="__('Total Approved visits by HHA')"
                :description="__('The first and last visit are performed by the admin; the rest by the assigned PT Assistant.')"
            />

            <flux:select wire:model.live="status" :label="__('Status')">
                @foreach (Patient::statusLabels() as $statusOption => $statusLabel)
                    <flux:select.option :value="$statusOption">{{ __($statusLabel) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Create patient') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
