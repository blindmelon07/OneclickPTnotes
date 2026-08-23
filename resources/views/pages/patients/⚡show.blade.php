<?php

use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Note;
use App\Models\Patient;
use App\Models\Visit;
use Flux\Flux;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Patient')] class extends Component {
    public Patient $patient;

    public string $name = '';

    public string $address = '';

    public string $phone = '';

    public string $diagnosis = '';

    public ?int $doctor_id = null;

    public ?int $insurance_company_id = null;

    public ?int $home_health_agency_id = null;

    public ?int $approved_visits = null;

    public string $cert_period = '';

    public ?string $date_referred = null;

    public ?string $date_of_ie = null;

    public ?string $date_of_re = null;

    public ?string $date_of_dc = null;

    public string $pt_freq = '';

    public ?int $pta_visits = null;

    public string $status = Patient::STATUS_ACTIVE;

    public string $visitScheduledAt = '';

    public string $visitType = Note::TYPE_FU;

    public function mount(Patient $patient): void
    {
        $this->patient = $patient;
        $this->fill($patient->only([
            'name', 'address', 'phone', 'diagnosis', 'doctor_id', 'insurance_company_id',
            'home_health_agency_id', 'approved_visits', 'cert_period', 'pt_freq', 'pta_visits', 'status',
        ]));

        $this->date_referred = $patient->date_referred?->toDateString();
        $this->date_of_ie = $patient->date_of_ie?->toDateString();
        $this->date_of_re = $patient->date_of_re?->toDateString();
        $this->date_of_dc = $patient->date_of_dc?->toDateString();
    }

    public function updatePatient(): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'diagnosis' => ['nullable', 'string', 'max:255'],
            'doctor_id' => ['nullable', 'exists:doctors,id'],
            'insurance_company_id' => ['nullable', 'exists:insurance_companies,id'],
            'home_health_agency_id' => ['nullable', 'exists:home_health_agencies,id'],
            'approved_visits' => ['nullable', 'integer', 'min:0'],
            'cert_period' => ['nullable', 'string', 'max:255'],
            'date_referred' => ['nullable', 'date'],
            'date_of_ie' => ['nullable', 'date'],
            'date_of_re' => ['nullable', 'date'],
            'date_of_dc' => ['nullable', 'date'],
            'pt_freq' => ['nullable', 'string', 'max:255'],
            'pta_visits' => ['nullable', 'integer', 'min:0'],
            'status' => ['required', 'in:'.implode(',', Patient::statuses())],
        ]);

        $this->patient->update($validated);

        Flux::modal('edit-patient')->close();

        Flux::toast(variant: 'success', text: __('Patient updated.'));
    }

    public function scheduleVisit(): void
    {
        $validated = $this->validate([
            'visitScheduledAt' => ['required', 'date'],
            'visitType' => ['required', 'in:'.implode(',', Note::types())],
        ]);

        $this->patient->visits()->create([
            'therapist_id' => auth()->id(),
            'visit_type' => $validated['visitType'],
            'scheduled_at' => $validated['visitScheduledAt'],
        ]);

        $this->reset(['visitScheduledAt']);

        Flux::modal('schedule-visit')->close();

        Flux::toast(variant: 'success', text: __('Visit scheduled.'));
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
     * @return Collection<int, Note>
     */
    #[Computed]
    public function notes(): Collection
    {
        return $this->patient->notes()->latest()->get();
    }

    /**
     * @return Collection<int, Visit>
     */
    #[Computed]
    public function upcomingVisits(): Collection
    {
        return $this->patient->visits()->with('therapist')->where('scheduled_at', '>=', now())->orderBy('scheduled_at')->get();
    }
}; ?>

<section class="w-full">
    <div class="mb-6 flex items-start justify-between gap-4">
        <div>
            <flux:heading size="xl" level="1">{{ $patient->name }}</flux:heading>
            <flux:badge :color="$patient->statusColor()" size="sm" class="mt-2">{{ ucfirst($patient->status) }}</flux:badge>
        </div>

        @can('patients.manage')
            <flux:modal.trigger name="edit-patient">
                <flux:button icon="pencil">{{ __('Edit') }}</flux:button>
            </flux:modal.trigger>
        @endcan
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-1 space-y-6">
            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('Demographics') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-zinc-500">{{ __('Address') }}</dt><dd>{{ $patient->address ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Phone') }}</dt><dd>{{ $patient->phone ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Diagnosis') }}</dt><dd>{{ $patient->diagnosis ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('Care team') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-zinc-500">{{ __('Doctor') }}</dt><dd>{{ $patient->doctor?->name ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Insurance') }}</dt><dd>{{ $patient->insuranceCompany?->name ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('HHA') }}</dt><dd>{{ $patient->homeHealthAgency?->name ?: '—' }}</dd></div>
                </dl>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('Treatment info') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div><dt class="text-zinc-500">{{ __('Approved visits') }}</dt><dd>{{ $patient->approved_visits ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Cert period') }}</dt><dd>{{ $patient->cert_period ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Date referred') }}</dt><dd>{{ $patient->date_referred?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Date of IE') }}</dt><dd>{{ $patient->date_of_ie?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Date of RE') }}</dt><dd>{{ $patient->date_of_re?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('Date of DC') }}</dt><dd>{{ $patient->date_of_dc?->format('M j, Y') ?? '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('PT frequency') }}</dt><dd>{{ $patient->pt_freq ?: '—' }}</dd></div>
                    <div><dt class="text-zinc-500">{{ __('# PTA visits') }}</dt><dd>{{ $patient->pta_visits ?? '—' }}</dd></div>
                </dl>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div>
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Notes') }}</flux:heading>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\Note::types() as $type)
                            <flux:button size="sm" :href="route('patients.notes.create', [$patient, $type])" wire:navigate>
                                {{ __('New :type', ['type' => strtoupper($type)]) }}
                            </flux:button>
                        @endforeach
                    </div>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Type') }}</flux:table.column>
                        <flux:table.column>{{ __('Author') }}</flux:table.column>
                        <flux:table.column>{{ __('Status') }}</flux:table.column>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->notes as $note)
                            <flux:table.row :key="$note->id">
                                <flux:table.cell variant="strong">
                                    <flux:link :href="route('notes.show', $note)" wire:navigate>{{ $note->label() }}</flux:link>
                                </flux:table.cell>
                                <flux:table.cell>{{ $note->author?->name }}</flux:table.cell>
                                <flux:table.cell>
                                    @if ($note->isEmailed())
                                        <flux:badge color="green" size="sm">{{ __('Emailed') }}</flux:badge>
                                    @elseif ($note->isSigned())
                                        <flux:badge color="amber" size="sm">{{ __('Signed') }}</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ __('Draft') }}</flux:badge>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $note->created_at->format('M j, Y') }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">{{ __('No notes yet.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

            <div>
                <div class="mb-3 flex items-center justify-between">
                    <flux:heading size="sm">{{ __('Upcoming visits') }}</flux:heading>
                    <flux:modal.trigger name="schedule-visit">
                        <flux:button size="sm" icon="calendar">{{ __('Schedule visit') }}</flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                        <flux:table.column>{{ __('Type') }}</flux:table.column>
                        <flux:table.column>{{ __('Therapist') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->upcomingVisits as $visit)
                            <flux:table.row :key="$visit->id">
                                <flux:table.cell>{{ $visit->scheduled_at->format('M j, Y g:i A') }}</flux:table.cell>
                                <flux:table.cell>{{ strtoupper($visit->visit_type) }}</flux:table.cell>
                                <flux:table.cell>{{ $visit->therapist?->name }}</flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="3">{{ __('No upcoming visits.') }}</flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>
        </div>
    </div>

    <flux:modal name="schedule-visit" class="md:w-96">
        <form wire:submit="scheduleVisit" class="space-y-4">
            <flux:heading size="lg">{{ __('Schedule visit') }}</flux:heading>
            <flux:input wire:model="visitScheduledAt" type="datetime-local" :label="__('Date & time')" required />
            <flux:select wire:model="visitType" :label="__('Visit type')">
                @foreach (\App\Models\Note::types() as $type)
                    <flux:select.option :value="$type">{{ strtoupper($type) }}</flux:select.option>
                @endforeach
            </flux:select>
            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Schedule') }}</flux:button>
            </div>
        </form>
    </flux:modal>

    <flux:modal name="edit-patient" class="md:w-[32rem]">
        <form wire:submit="updatePatient" class="max-h-[70vh] space-y-4 overflow-y-auto pe-1">
            <flux:heading size="lg">{{ __('Edit patient') }}</flux:heading>

            <flux:input wire:model="name" :label="__('Name')" required />
            <flux:input wire:model="address" :label="__('Address')" />
            <flux:input wire:model="phone" :label="__('Phone')" />
            <flux:input wire:model="diagnosis" :label="__('Diagnosis')" />

            <flux:select wire:model="doctor_id" :label="__('Doctor')" :placeholder="__('Select doctor')">
                @foreach ($this->doctors as $doctor)
                    <flux:select.option :value="$doctor->id">{{ $doctor->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="insurance_company_id" :label="__('Insurance')" :placeholder="__('Select insurance')">
                @foreach ($this->insuranceCompanies as $insurance)
                    <flux:select.option :value="$insurance->id">{{ $insurance->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:select wire:model="home_health_agency_id" :label="__('HHA')" :placeholder="__('Select HHA')">
                @foreach ($this->homeHealthAgencies as $hha)
                    <flux:select.option :value="$hha->id">{{ $hha->name }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="approved_visits" type="number" :label="__('Approved visits')" />
            <flux:input wire:model="cert_period" :label="__('Cert period')" />
            <flux:input wire:model="date_referred" type="date" :label="__('Date referred')" />
            <flux:input wire:model="date_of_ie" type="date" :label="__('Date of IE')" />
            <flux:input wire:model="date_of_re" type="date" :label="__('Date of RE')" />
            <flux:input wire:model="date_of_dc" type="date" :label="__('Date of DC')" />
            <flux:input wire:model="pt_freq" :label="__('PT frequency')" />
            <flux:input wire:model="pta_visits" type="number" :label="__('# PTA visits')" />

            <flux:select wire:model="status" :label="__('Status')">
                @foreach (Patient::statuses() as $statusOption)
                    <flux:select.option :value="$statusOption">{{ ucfirst($statusOption) }}</flux:select.option>
                @endforeach
            </flux:select>

            <div class="flex">
                <flux:spacer />
                <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </flux:modal>
</section>
