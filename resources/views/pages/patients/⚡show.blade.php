<?php

use App\Models\Doctor;
use App\Models\HomeHealthAgency;
use App\Models\InsuranceCompany;
use App\Models\Note;
use App\Models\Patient;
use App\Models\User;
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

    public ?int $pt_assistant_id = null;

    public ?int $approved_visits = null;

    public string $cert_period = '';

    public ?string $date_referred = null;

    public ?string $date_of_ie = null;

    public ?string $date_of_re = null;

    public ?string $date_of_dc = null;

    public string $pt_freq = '';

    public ?int $pta_visits = null;

    public string $status = Patient::STATUS_ACTIVE;

    /**
     * Visits are no longer typed when scheduled — every one is booked as a
     * follow-up, which is also the rate it bills at (see `config/billing.php`).
     */
    public const DEFAULT_VISIT_TYPE = Note::TYPE_FU;

    public string $visitScheduledAt = '';

    public function mount(Patient $patient): void
    {
        abort_unless($patient->isVisibleTo(auth()->user()), 403);

        $this->patient = $patient;

        // Columns left empty stay null in the database, but the string-typed
        // properties below cannot take null — drop them and keep the '' default.
        $this->fill(array_filter($patient->only([
            'name', 'address', 'phone', 'diagnosis', 'doctor_id', 'insurance_company_id',
            'home_health_agency_id', 'pt_assistant_id', 'approved_visits', 'cert_period', 'pt_freq', 'pta_visits', 'status',
        ]), fn (mixed $value) => $value !== null));

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
            'pt_assistant_id' => ['nullable', 'exists:users,id'],
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

        if (auth()->user()->isRestrictedToAssignedPatients()) {
            unset($validated['pt_assistant_id']);
        }

        // Untouched text fields arrive as '' — store them as null so an empty
        // column stays empty rather than becoming a blank string.
        $this->patient->update(array_map(
            fn (mixed $value) => $value === '' ? null : $value,
            $validated,
        ));

        Flux::modal('edit-patient')->close();

        Flux::toast(variant: 'success', text: __('Patient updated.'));
    }

    /**
     * Assign (or clear) the patient's PT Assistant directly, without going
     * through the edit form.
     */
    public function assignPtAssistant(?int $userId = null): void
    {
        abort_unless(auth()->user()->can('patients.manage'), 403);
        abort_if(auth()->user()->isRestrictedToAssignedPatients(), 403);

        abort_unless(
            $userId === null || $this->ptAssistants->contains('id', $userId),
            422,
        );

        $this->patient->update(['pt_assistant_id' => $userId]);

        $this->pt_assistant_id = $userId;

        $this->patient->refresh();

        Flux::toast(variant: 'success', text: $userId
            ? __('Assigned to :name.', ['name' => $this->patient->ptAssistant->name])
            : __('PT Assistant cleared.'));
    }

    public function scheduleVisit(): void
    {
        $validated = $this->validate([
            'visitScheduledAt' => ['required', 'date'],
        ]);

        $sequence = $this->patient->nextVisitNumber();

        $this->patient->visits()->create([
            'therapist_id' => $this->patient->therapistForVisitNumber($sequence)?->id ?? auth()->id(),
            'visit_type' => self::DEFAULT_VISIT_TYPE,
            'scheduled_at' => $validated['visitScheduledAt'],
        ]);

        $this->reset(['visitScheduledAt']);

        // Computed properties cache per request — drop them so the table and
        // the counts re-read the visit just created.
        unset($this->upcomingVisits, $this->visitProgress, $this->visitNumbers);

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
     * @return Collection<int, User>
     */
    #[Computed]
    public function ptAssistants(): Collection
    {
        return User::havingRole(User::ROLE_PT_ASSISTANT)->orderBy('name')->get();
    }

    /**
     * Where this patient stands in their course of care.
     *
     * @return array{approved: int|null, scheduled: int, remaining: int|null}
     */
    #[Computed]
    public function visitProgress(): array
    {
        $approved = $this->patient->approved_visits;
        $scheduled = $this->patient->visits()->count();

        return [
            'approved' => $approved,
            'scheduled' => $scheduled,
            'remaining' => $approved === null ? null : max(0, (int) $approved - $scheduled),
        ];
    }

    /**
     * Each visit's position in the course, keyed by visit id — the same
     * ordering `Patient::therapistForVisitNumber()` assigns therapists by.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function visitNumbers(): array
    {
        return $this->patient->visits()
            ->orderBy('id')
            ->pluck('id')
            ->flip()
            ->map(fn (int $index) => $index + 1)
            ->all();
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

    <div class="grid min-w-0 gap-6 lg:grid-cols-3">
        <div class="min-w-0 lg:col-span-1 space-y-6">
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
                    <div>
                        <dt class="text-zinc-500">{{ __('PT Assistant') }}</dt>
                        <dd class="flex flex-wrap items-center gap-2">
                            <span>{{ $patient->ptAssistant?->name ?: '—' }}</span>

                            @can('patients.manage')
                                @unless (auth()->user()->isRestrictedToAssignedPatients())
                                    <flux:dropdown>
                                        <flux:button size="xs" variant="ghost" icon="pencil">{{ __('Assign') }}</flux:button>

                                        <flux:menu>
                                            @forelse ($this->ptAssistants as $assistant)
                                                <flux:menu.item wire:click="assignPtAssistant({{ $assistant->id }})">
                                                    {{ $assistant->name }}
                                                </flux:menu.item>
                                            @empty
                                                <flux:menu.item disabled>{{ __('No PT Assistants yet') }}</flux:menu.item>
                                            @endforelse

                                            @if ($patient->pt_assistant_id)
                                                <flux:menu.separator />
                                                <flux:menu.item variant="danger" wire:click="assignPtAssistant">
                                                    {{ __('Clear assignment') }}
                                                </flux:menu.item>
                                            @endif
                                        </flux:menu>
                                    </flux:dropdown>
                                @endunless
                            @endcan
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="rounded-xl border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:heading size="sm" class="mb-3">{{ __('Visits') }}</flux:heading>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-zinc-500">{{ __('Approved') }}</dt>
                        <dd>{{ $this->visitProgress['approved'] ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Scheduled') }}</dt>
                        <dd>
                            {{ $this->visitProgress['scheduled'] }}
                            @if ($this->visitProgress['approved'])
                                {{ __('of :approved', ['approved' => $this->visitProgress['approved']]) }}
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-zinc-500">{{ __('Remaining') }}</dt>
                        <dd>{{ $this->visitProgress['remaining'] ?? '—' }}</dd>
                    </div>
                </dl>

                @if ($this->visitProgress['approved'] && $this->visitProgress['scheduled'] > $this->visitProgress['approved'])
                    <flux:badge color="amber" size="sm" class="mt-3">{{ __('Over approved count') }}</flux:badge>
                @endif

                <flux:text class="mt-3 text-xs">
                    {{ __('The first and last visit are performed by the admin; the rest by the assigned PT Assistant.') }}
                </flux:text>
            </div>
        </div>

        <div class="min-w-0 lg:col-span-2 space-y-6">
            <div>
                <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <flux:heading size="sm">{{ __('Upcoming visits') }}</flux:heading>
                    <flux:modal.trigger name="schedule-visit">
                        <flux:button size="sm" icon="calendar">{{ __('Schedule visit') }}</flux:button>
                    </flux:modal.trigger>
                </div>

                <flux:table>
                    <flux:table.columns>
                        <flux:table.column>{{ __('Visit') }}</flux:table.column>
                        <flux:table.column>{{ __('Date') }}</flux:table.column>
                        <flux:table.column>{{ __('Therapist') }}</flux:table.column>
                        <flux:table.column>{{ __('Documentation') }}</flux:table.column>
                    </flux:table.columns>

                    <flux:table.rows>
                        @forelse ($this->upcomingVisits as $visit)
                            <flux:table.row :key="$visit->id">
                                <flux:table.cell variant="strong">
                                    {{ $this->visitNumbers[$visit->id] ?? '—' }}
                                    @if ($this->visitProgress['approved'])
                                        <span class="text-zinc-500">{{ __('of :approved', ['approved' => $this->visitProgress['approved']]) }}</span>
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell>{{ $visit->scheduled_at->format('M j, Y g:i A') }}</flux:table.cell>
                                <flux:table.cell>{{ $visit->therapist?->name }}</flux:table.cell>
                                <flux:table.cell>
                                    <flux:button size="sm" variant="primary" icon="arrow-right" :href="route('patients.visits.document', [$patient, $visit])" wire:navigate>
                                        {{ __('Proceed') }}
                                    </flux:button>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4">{{ __('No upcoming visits.') }}</flux:table.cell>
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

            @unless (auth()->user()->isRestrictedToAssignedPatients())
                <flux:select wire:model.live="pt_assistant_id" :label="__('PT Assistant')" :placeholder="__('Select PT Assistant')">
                    @foreach ($this->ptAssistants as $ptAssistant)
                        <flux:select.option :value="$ptAssistant->id">{{ $ptAssistant->name }}</flux:select.option>
                    @endforeach
                </flux:select>
            @endunless

            <flux:input wire:model="approved_visits" type="number" :label="__('Approved visits')" />
            <flux:input wire:model="cert_period" :label="__('Cert period')" />
            <flux:input wire:model="date_referred" type="date" :label="__('Date referred')" />
            <flux:input wire:model="date_of_ie" type="date" :label="__('Date of IE')" />
            <flux:input wire:model="date_of_re" type="date" :label="__('Date of RE')" />
            <flux:input wire:model="date_of_dc" type="date" :label="__('Date of DC')" />
            <flux:input wire:model="pt_freq" :label="__('PT frequency')" />
            <flux:input wire:model="pta_visits" type="number" :label="__('# PTA visits')" />

            <flux:select wire:model.live="status" :label="__('Status')">
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
