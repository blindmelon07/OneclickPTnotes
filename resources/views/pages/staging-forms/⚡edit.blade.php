<?php

use App\Models\StagingForm;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Staging Form')] class extends Component {
    public ?StagingForm $stagingForm = null;

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(?StagingForm $stagingForm = null): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->stagingForm = $stagingForm?->exists ? $stagingForm : null;
        $this->data = $this->stagingForm?->formData() ?? StagingForm::blankData();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $text = ['nullable', 'string', 'max:255'];
        $longText = ['nullable', 'string', 'max:5000'];

        return [
            'data.agency_name' => ['required', 'string', 'max:255'],
            'data.patient_name' => ['required', 'string', 'max:255'],
            'data.mr_number' => $text,
            'data.blood_pressure' => ['nullable', 'string', 'max:20'],
            'data.heart_rate' => ['nullable', 'string', 'max:20'],
            'data.diagnosis' => $text,
            'data.evaluation_date' => ['nullable', 'date'],
            'data.time_in' => ['nullable', 'date_format:H:i'],
            'data.time_out' => ['nullable', 'date_format:H:i'],
            'data.problems' => $longText,
            'data.short_term_goals' => $longText,
            'data.functional_level' => ['nullable', Rule::in(StagingForm::assistLevels())],
            'data.functional_overrides' => ['array'],
            'data.functional_overrides.*' => ['nullable', Rule::in(StagingForm::assistLevels())],
            'data.gait' => ['array'],
            'data.gait.*' => $text,
            'data.functional_limitations' => ['array'],
            'data.functional_limitations.*' => [Rule::in(StagingForm::functionalLimitations())],
            'data.functional_limitations_other' => $text,
            'data.homebound_reasons' => ['array'],
            'data.homebound_reasons.*' => [Rule::in(StagingForm::homeboundReasons())],
            'data.cannot_ambulate_distance' => $text,
            'data.cognition' => ['array'],
            'data.cognition.*' => [Rule::in(StagingForm::cognitionOptions())],
            'data.cognition_other' => $text,
            'data.balance.*' => ['nullable', Rule::in(StagingForm::balanceGrades())],
            'data.living_situation' => $text,
            'data.precautions' => ['array'],
            'data.precautions.*' => [Rule::in(StagingForm::precautionOptions())],
            'data.precautions_other' => $text,
            'data.rehab_potential' => ['nullable', Rule::in(StagingForm::rehabPotentials())],
            'data.rom_exceptions' => $longText,
            'data.mmt_exceptions' => $longText,
            'data.activities_permitted' => ['array'],
            'data.activities_permitted.*' => [Rule::in(StagingForm::activitiesPermitted())],
            'data.wheelchair_skills' => $longText,
            'data.prior_level_of_function' => $longText,
            'data.palpation' => $longText,
            'data.pain' => $longText,
            'data.dme_needed' => $longText,
            'data.comments' => $longText,
            'data.pt_frequency' => $text,
            'data.discharge_plans' => $longText,
            'data.plan_of_care' => $longText,
            'data.pta_name' => $text,
            'data.patient_address' => $text,
            'data.patient_phone' => $text,
            'data.pta_start_week' => $text,
            'data.pta_frequency' => $text,
            'data.pta_schedule' => ['array', 'size:'.StagingForm::PTA_SCHEDULE_ROWS],
            'data.pta_schedule.*.week_of' => $text,
            'data.pta_schedule.*.visits' => ['nullable', 'integer', 'min:0', 'max:99'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'data.agency_name' => __('agency name'),
            'data.patient_name' => __('patient name'),
            'data.pta_schedule.*.visits' => __('# of visits'),
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->validate();

        $attributes = [
            'patient_name' => $this->data['patient_name'],
            'agency_name' => $this->data['agency_name'],
            'data' => $this->data,
        ];

        if ($this->stagingForm) {
            $this->stagingForm->update($attributes);
        } else {
            $this->stagingForm = StagingForm::create([...$attributes, 'created_by' => auth()->id()]);
        }

        Flux::toast(variant: 'success', text: __('Staging form saved.'));

        $this->redirect(route('staging-forms.show', $this->stagingForm), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $stagingForm ? __('Edit staging form') : __('New staging form') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('P.T. / O.T. Evaluation — the Visit Note, Treatment Order, PTA instructions and Team Conference copy from this form.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex flex-wrap items-center gap-4 text-sm">
        <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-sm bg-yellow-300"></span>{{ __('Yellow — filled in by the VA') }}</span>
        <span class="flex items-center gap-2"><span class="inline-block size-3 rounded-sm bg-green-500"></span>{{ __('Green — fixed, cannot be edited') }}</span>
    </div>

    <form wire:submit="save" class="max-w-4xl space-y-8">
        @php($section = 'space-y-4 rounded-xl border-l-4 border-yellow-300 bg-yellow-50/60 p-4 dark:bg-yellow-400/5')

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Patient') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="data.agency_name" :label="__('Agency name')" required />
                <flux:input wire:model="data.patient_name" :label="__('Patient name')" required />
            </div>
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:input wire:model="data.mr_number" :label="__('MR #')" />
                <flux:input wire:model="data.blood_pressure" :label="__('BP')" />
                <flux:input wire:model="data.heart_rate" :label="__('HR')" />
                <flux:input wire:model="data.evaluation_date" type="date" :label="__('Date')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="data.diagnosis" :label="__('Diagnosis')" class="sm:col-span-1" />
                <flux:input wire:model="data.time_in" type="time" :label="__('Time in')" />
                <flux:input wire:model="data.time_out" type="time" :label="__('Time out')" />
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:textarea wire:model="data.problems" rows="5" :label="__('Problems identified')" />
                <flux:textarea wire:model="data.short_term_goals" rows="5" :label="__('Short term goal (4–8 weeks)')" />
            </div>
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Functional level') }}</flux:heading>
            <flux:select wire:model.live="data.functional_level" :label="__('Overall level')" :description="__('Copies to every activity below unless you pick a different level for it.')" class="max-w-xs">
                <flux:select.option value="">{{ __('—') }}</flux:select.option>
                @foreach (StagingForm::assistLevels() as $level)
                    <flux:select.option :value="$level">{{ $level }}</flux:select.option>
                @endforeach
            </flux:select>

            @foreach (collect(StagingForm::functionalActivities())->groupBy('group', preserveKeys: true) as $group => $activities)
                <div>
                    <flux:label>{{ __($group) }}</flux:label>
                    <div class="mt-2 grid gap-3 sm:grid-cols-3">
                        @foreach ($activities as $key => $activity)
                            <flux:select wire:model="data.functional_overrides.{{ $key }}" :label="__($activity['label'])" size="sm">
                                <flux:select.option value="">{{ __('Same as overall') }}{{ $data['functional_level'] ? ' ('.$data['functional_level'].')' : '' }}</flux:select.option>
                                @foreach (StagingForm::assistLevels() as $level)
                                    <flux:select.option :value="$level">{{ $level }}</flux:select.option>
                                @endforeach
                            </flux:select>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Gait description') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach (StagingForm::gaitFields() as $key => $label)
                    <flux:input
                        wire:model="data.gait.{{ $key }}"
                        :label="__($label)"
                        :description="$key === 'minutes_distance' ? __('Leave blank to copy Level surfaces.') : null"
                    />
                @endforeach
            </div>
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Functional limitations') }}</flux:heading>
            <div class="grid gap-1 sm:grid-cols-3">
                @foreach (StagingForm::functionalLimitations() as $option)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="data.functional_limitations" value="{{ $option }}" />
                        <flux:label>{{ $option }}</flux:label>
                    </flux:field>
                @endforeach
            </div>
            <flux:input wire:model="data.functional_limitations_other" :label="__('Others')" />
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Homebound reasons') }}</flux:heading>
            <div class="grid gap-1 sm:grid-cols-2">
                @foreach (StagingForm::homeboundReasons() as $option)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="data.homebound_reasons" value="{{ $option }}" />
                        <flux:label>{{ $option }}</flux:label>
                    </flux:field>
                @endforeach
            </div>
            <flux:input wire:model="data.cannot_ambulate_distance" :label="__('Cannot ambulate > (feet / mins)')" :description="__('Leave blank to copy Level surfaces.')" class="max-w-xs" />
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Cognition') }}</flux:heading>
            <div class="grid gap-1 sm:grid-cols-4">
                @foreach (StagingForm::cognitionOptions() as $option)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="data.cognition" value="{{ $option }}" />
                        <flux:label>{{ $option }}</flux:label>
                    </flux:field>
                @endforeach
            </div>
            <flux:input wire:model="data.cognition_other" :label="__('Others')" />
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Balance / tolerance') }}</flux:heading>
            <flux:text>{{ __('Dynamic copies static unless you pick a different grade.') }}</flux:text>
            <div class="grid gap-4 sm:grid-cols-4">
                @foreach (['sitting_static' => 'Sitting — static', 'sitting_dynamic' => 'Sitting — dynamic', 'standing_static' => 'Standing — static', 'standing_dynamic' => 'Standing — dynamic'] as $key => $label)
                    <flux:select wire:model="data.balance.{{ $key }}" :label="__($label)">
                        <flux:select.option value="">{{ str_ends_with($key, 'dynamic') ? __('Same as static') : __('—') }}</flux:select.option>
                        @foreach (StagingForm::balanceGrades() as $grade)
                            <flux:select.option :value="$grade">{{ $grade }}</flux:select.option>
                        @endforeach
                    </flux:select>
                @endforeach
            </div>
            <flux:input wire:model="data.living_situation" :label="__('Living situation')" />
            <div>
                <flux:label>{{ __('Precautions') }}</flux:label>
                <div class="mt-2 grid gap-1 sm:grid-cols-5">
                    @foreach (StagingForm::precautionOptions() as $option)
                        <flux:field variant="inline">
                            <flux:checkbox wire:model="data.precautions" value="{{ $option }}" />
                            <flux:label>{{ $option }}</flux:label>
                        </flux:field>
                    @endforeach
                </div>
            </div>
            <flux:input wire:model="data.precautions_other" :label="__('Other precautions')" />
            <flux:radio.group wire:model="data.rehab_potential" :label="__('Rehab potential')" variant="segmented" class="max-w-md">
                @foreach (StagingForm::rehabPotentials() as $potential)
                    <flux:radio :value="$potential" :label="$potential" />
                @endforeach
            </flux:radio.group>
        </div>

        <div class="{{ $section }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:textarea wire:model="data.rom_exceptions" rows="3" :label="__('ROM: all joints are WFL / WNL except for')" />
                <flux:textarea wire:model="data.mmt_exceptions" rows="3" :label="__('MMT: all paraspinal & both UE/LE are WFL except for')" />
            </div>
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Activities permitted') }}</flux:heading>
            <div class="grid gap-1 sm:grid-cols-3">
                @foreach (StagingForm::activitiesPermitted() as $option)
                    <flux:field variant="inline">
                        <flux:checkbox wire:model="data.activities_permitted" value="{{ $option }}" />
                        <flux:label>{{ $option }}</flux:label>
                    </flux:field>
                @endforeach
            </div>
        </div>

        <div class="{{ $section }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:textarea wire:model="data.wheelchair_skills" rows="3" :label="__('Wheelchair skills')" />
                <flux:textarea wire:model="data.prior_level_of_function" rows="3" :label="__('Prior level of function')" />
                <flux:textarea wire:model="data.palpation" rows="3" :label="__('Palpation')" />
                <flux:textarea wire:model="data.pain" rows="3" :label="__('Pain (location / intensity)')" />
                <flux:textarea wire:model="data.dme_needed" rows="3" :label="__('DME needed')" />
                <flux:textarea wire:model="data.comments" rows="3" :label="__('Comments')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:input wire:model="data.pt_frequency" :label="__('PT frequency')" />
                <flux:input wire:model="data.discharge_plans" :label="__('Discharge plans')" />
            </div>
            <flux:textarea wire:model="data.plan_of_care" rows="4" :label="__('Plan of care')" />
        </div>

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('PTA instructions') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-3">
                <flux:input wire:model="data.pta_name" :label="__('PTA name')" />
                <flux:input wire:model="data.patient_address" :label="__('Patient address')" class="sm:col-span-2" />
                <flux:input wire:model="data.patient_phone" :label="__('Patient tel #')" />
                <flux:input wire:model="data.pta_start_week" :label="__('When to see (week)')" />
                <flux:input wire:model="data.pta_frequency" :label="__('PTA frequency')" />
            </div>

            <div class="space-y-2">
                <flux:label>{{ __('Visit schedule') }}</flux:label>
                @foreach ($data['pta_schedule'] as $index => $row)
                    <div class="grid grid-cols-3 gap-3" wire:key="pta-row-{{ $index }}">
                        <flux:input wire:model="data.pta_schedule.{{ $index }}.week_of" :placeholder="__('Week of')" class="col-span-2" />
                        <flux:input wire:model.live.blur="data.pta_schedule.{{ $index }}.visits" type="number" min="0" :placeholder="__('# of visits')" />
                    </div>
                @endforeach
                <flux:error name="data.pta_schedule.*.visits" />
            </div>
        </div>

        <div class="space-y-3 rounded-xl border-l-4 border-green-500 bg-green-50/60 p-4 dark:bg-green-500/5">
            <div class="flex items-center gap-2">
                <flux:heading size="lg">{{ __('Fixed on the companion forms') }}</flux:heading>
                <flux:badge color="green" size="sm" icon="lock-closed">{{ __('Locked') }}</flux:badge>
            </div>
            <flux:text>
                {{ __('Visit Note services') }}: {{ implode(', ', StagingForm::fixedVisitServices()) }}.
            </flux:text>
            <flux:text>
                {{ __('Team Conference (PT)') }}: {{ implode(', ', StagingForm::fixedTeamConferenceProblems()) }}.
            </flux:text>
            <flux:text>
                {{ __('Progress towards goal and Treatment Order goals copy the short term goal. PTA total visits') }}:
                <strong>{{ array_sum(array_map(fn ($row) => (int) $row['visits'], $data['pta_schedule'])) }}</strong>
            </flux:text>
        </div>

        <div class="flex items-center gap-2">
            <flux:button :href="$stagingForm ? route('staging-forms.show', $stagingForm) : route('staging-forms.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:spacer />
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</section>
