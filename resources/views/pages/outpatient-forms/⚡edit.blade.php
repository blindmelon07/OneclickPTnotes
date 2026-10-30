<?php

use App\Models\OutpatientForm;
use Flux\Flux;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Outpatient Form')] class extends Component {
    public ?OutpatientForm $outpatientForm = null;

    /** @var array<string, mixed> */
    public array $data = [];

    public string $tab = 'ie';

    public function mount(?OutpatientForm $outpatientForm = null): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->outpatientForm = $outpatientForm?->exists ? $outpatientForm : null;
        $this->data = $this->outpatientForm?->formData() ?? OutpatientForm::blankData();
    }

    public function updatedTab(): void
    {
        if (! in_array($this->tab, ['ie', 're', 'dc'], true)) {
            $this->tab = 'ie';
        }
    }

    /**
     * An unsaved copy of the case, so the RE and DC can show what they copy
     * from the IE as it is being filled in.
     */
    #[Computed]
    public function preview(): OutpatientForm
    {
        return new OutpatientForm(['data' => $this->data]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        $text = ['nullable', 'string', 'max:255'];
        $longText = ['nullable', 'string', 'max:5000'];
        $date = ['nullable', 'date'];
        $count = ['nullable', 'integer', 'min:0', 'max:999'];

        $rules = [
            'data.patient_name' => ['required', 'string', 'max:255'],
            'data.age' => ['nullable', 'integer', 'min:0', 'max:130'],
            'data.dob' => $date,
            'data.hand' => ['nullable', Rule::in(OutpatientForm::hands())],
            'data.diagnosis' => $text,
            'data.doi' => $date,
            'data.dosx' => $text,

            'data.ie.date' => $date,
            'data.ie.history' => $longText,
            'data.ie.chief_complaints' => $longText,
            'data.ie.rom' => $longText,
            'data.ie.mmt' => $longText,
            'data.ie.grip.*' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'data.ie.pinch.*.*' => ['nullable', 'numeric', 'min:0', 'max:999'],
            'data.ie.gait_wnl' => ['boolean'],
            'data.ie.assistive_device' => $text,
            'data.ie.walk_distance' => $text,
            'data.ie.walk_with_device' => ['nullable', Rule::in(['with', 'without'])],
            'data.ie.person_assist' => $text,
            'data.ie.stair_flights' => $text,
            'data.ie.sensory' => ['nullable', Rule::in(['intact', 'tingling'])],
            'data.ie.numbness_on' => $text,
            'data.ie.xray' => ['nullable', Rule::in(['Y', 'N'])],
            'data.ie.xray_findings' => $text,
            'data.ie.mri' => ['nullable', Rule::in(['Y', 'N'])],
            'data.ie.mri_findings' => $text,
            'data.ie.special_test' => $longText,
            'data.ie.palpation_sign' => ['nullable', Rule::in(['+', '-'])],
            'data.ie.palpation_grade' => $text,
            'data.ie.tenderness_on' => $text,
            'data.ie.work_status' => ['array'],
            'data.ie.work_status.*' => [Rule::in(OutpatientForm::workStatuses())],
            'data.ie.adl_wnl' => ['boolean'],
            'data.ie.adl_difficulties' => ['array'],
            'data.ie.adl_difficulties.*' => [Rule::in(OutpatientForm::adlDifficulties())],
            'data.ie.adl_other' => $text,
            'data.ie.precautions' => ['array'],
            'data.ie.precautions.*' => [Rule::in(OutpatientForm::precautions())],
            'data.ie.precaution_details.*' => $text,
            'data.ie.precautions_other' => $text,
            'data.ie.goals' => ['array'],
            'data.ie.goals.*' => [Rule::in(array_keys(OutpatientForm::goals()))],
            'data.ie.goal_details.*' => $text,
            'data.ie.rehab_potential' => ['nullable', Rule::in(OutpatientForm::rehabPotentials())],
            'data.ie.rx_expiration_date' => $date,
            'data.ie.total_authorized_visits' => $count,
            'data.ie.frequency' => $count,
            'data.ie.duration' => $count,

            'data.re.date' => $date,
            'data.re.progress.*.status' => ['nullable', Rule::in(OutpatientForm::reEvaluationStatuses())],
            'data.re.pain_meds' => ['nullable', Rule::in(['+', '-'])],
            'data.re.objectives' => $longText,
            'data.re.working' => ['nullable', Rule::in(OutpatientForm::reEvaluationWorkingOptions())],
            'data.re.comments' => $longText,
            'data.re.precautions' => $text,
            'data.re.still_complains' => $text,
            'data.re.will_benefit' => $text,

            'data.dc.date' => $date,
            'data.dc.progress.*.status' => ['nullable', Rule::in(OutpatientForm::dischargeStatuses())],
            'data.dc.pain_meds_initial' => ['nullable', Rule::in(['+', '-'])],
            'data.dc.pain_meds_end' => ['nullable', Rule::in(['+', '-'])],
            'data.dc.goals_status' => ['nullable', Rule::in(OutpatientForm::goalStatuses())],
            'data.dc.goals_other' => $text,
            'data.dc.comments' => $longText,
            'data.dc.working' => ['nullable', Rule::in(['Yes', 'No'])],
            'data.dc.work_duty' => ['nullable', Rule::in(OutpatientForm::workDuties())],
            'data.dc.still_complains' => $text,
            'data.dc.benefit_decrease' => $text,
            'data.dc.benefit_increase' => $text,
            'data.dc.discharge_reasons' => ['array'],
            'data.dc.discharge_reasons.*' => [Rule::in(OutpatientForm::dischargeReasons())],
            'data.dc.noncompliant_reason' => $text,
            'data.dc.discharge_reasons_other' => $text,
            'data.dc.surgery_date' => $date,
            'data.dc.surgery_on' => $text,
            'data.dc.total_visits' => $count,
        ];

        foreach (['ie', 're', 'dc'] as $form) {
            $rules["data.{$form}.plan.*"] = $longText;
        }

        foreach (['re', 'dc'] as $form) {
            $rules += [
                "data.{$form}.progress.*.value" => $text,
                "data.{$form}.progress.*.baseline" => $text,
                "data.{$form}.others_label" => $text,
                "data.{$form}.grip.*" => ['nullable', 'numeric', 'min:0', 'max:999'],
                "data.{$form}.recommendation" => ['nullable', Rule::in(array_keys(OutpatientForm::recommendations()))],
                "data.{$form}.visits_left" => $count,
                "data.{$form}.discharge_reason" => $text,
                "data.{$form}.continued_frequency" => $count,
                "data.{$form}.continued_duration" => $count,
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            'data.patient_name' => __('patient name'),
            'data.ie.grip.*' => __('grip'),
            'data.ie.pinch.*.*' => __('pinch'),
            'data.re.grip.*' => __('grip'),
            'data.dc.grip.*' => __('grip'),
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('notes.manage'), 403);

        $this->validate();

        $attributes = [
            'patient_name' => $this->data['patient_name'],
            'diagnosis' => $this->data['diagnosis'],
            'data' => $this->data,
        ];

        if ($this->outpatientForm) {
            $this->outpatientForm->update($attributes);
        } else {
            $this->outpatientForm = OutpatientForm::create([...$attributes, 'created_by' => auth()->id()]);
        }

        Flux::toast(variant: 'success', text: __('Outpatient form saved.'));

        $this->redirect(route('outpatient-forms.show', $this->outpatientForm), navigate: true);
    }
}; ?>

<section class="w-full">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $outpatientForm ? __('Edit outpatient form') : __('New outpatient form') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">{{ __('Initial Evaluation, Re-Evaluation and Discharge Summary for one case — the RE and DC baselines copy from the IE.') }}</flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    @php($model = OutpatientForm::class)
    @php($preview = $this->preview)
    @php($section = 'space-y-4 rounded-xl border border-zinc-200 p-4 dark:border-zinc-700')

    <form wire:submit="save" class="max-w-5xl space-y-8">
        @if ($errors->any())
            <flux:callout variant="danger" icon="exclamation-triangle" :heading="__('Some fields need fixing — check each tab.')">
                <flux:callout.text>
                    <ul class="list-disc ps-5">
                        @foreach ($errors->all() as $message)
                            <li>{{ $message }}</li>
                        @endforeach
                    </ul>
                </flux:callout.text>
            </flux:callout>
        @endif

        <div class="{{ $section }}">
            <flux:heading size="lg">{{ __('Patient') }}</flux:heading>
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:input wire:model="data.patient_name" :label="__('Patient name')" required class="sm:col-span-2" />
                <flux:input wire:model="data.age" type="number" min="0" :label="__('Age')" />
                <flux:input wire:model="data.dob" type="date" :label="__('DOB')" />
            </div>
            <div class="grid gap-4 sm:grid-cols-4">
                <flux:input wire:model="data.diagnosis" :label="__('Dx')" class="sm:col-span-2" />
                <flux:input wire:model="data.doi" type="date" :label="__('DOI')" />
                <flux:input wire:model="data.dosx" :label="__('DOSx')" />
            </div>
            <flux:radio.group wire:model="data.hand" :label="__('Dominant hand')" variant="segmented" class="max-w-xs">
                @foreach ($model::hands() as $hand)
                    <flux:radio :value="$hand" :label="$hand" />
                @endforeach
            </flux:radio.group>
        </div>

        <div class="flex gap-1 rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
            @foreach ($model::documents() as $key => $label)
                @php($tabKey = ['initial-evaluation' => 'ie', 're-evaluation' => 're', 'discharge-summary' => 'dc'][$key])
                <button
                    type="button"
                    wire:click="$set('tab', '{{ $tabKey }}')"
                    @class([
                        'flex-1 rounded-md px-3 py-2 text-sm font-medium',
                        'bg-white shadow-sm dark:bg-zinc-600' => $tab === $tabKey,
                        'text-zinc-500' => $tab !== $tabKey,
                    ])
                >
                    {{ __($label) }}
                </button>
            @endforeach
        </div>

        {{-- Initial evaluation --}}
        @if ($tab === 'ie')
        <div class="space-y-8">
            <div class="{{ $section }}">
                <flux:input wire:model.blur="data.ie.date" type="date" :label="__('Evaluation date')" class="max-w-xs" />
                <flux:textarea wire:model="data.ie.history" rows="3" :label="__('Hx')" />
                <flux:textarea wire:model="data.ie.chief_complaints" rows="3" :label="__('Chief complaint(s)')" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:textarea wire:model.blur="data.ie.rom" rows="3" :label="__('ROM (measured in degrees)')" />
                    <flux:textarea wire:model.blur="data.ie.mmt" rows="3" :label="__('MMT')" />
                </div>
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Grip / pinch (lbs)') }}</flux:heading>
                <div class="grid max-w-md grid-cols-2 gap-4">
                    <flux:input wire:model="data.ie.grip.right" :label="__('Grip R')" />
                    <flux:input wire:model="data.ie.grip.left" :label="__('Grip L')" />
                </div>
                @foreach (['right' => 'Pinch — right', 'left' => 'Pinch — left'] as $side => $label)
                    <div>
                        <flux:label>{{ __($label) }}</flux:label>
                        <div class="mt-2 grid grid-cols-4 gap-3">
                            @foreach (['ti' => 'T/I', 'tm' => 'T/M', 'tr' => 'T/R', 'tl' => 'T/L'] as $finger => $fingerLabel)
                                <flux:input wire:model="data.ie.pinch.{{ $side }}.{{ $finger }}" :label="$fingerLabel" size="sm" />
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <flux:error name="data.ie.grip.*" />
                <flux:error name="data.ie.pinch.*.*" />
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Gait / stair / step tolerance') }}</flux:heading>
                <flux:field variant="inline">
                    <flux:checkbox wire:model.live="data.ie.gait_wnl" />
                    <flux:label>{{ __('WNL') }}</flux:label>
                </flux:field>
                <div class="grid gap-4 sm:grid-cols-3">
                    <flux:input wire:model.blur="data.ie.assistive_device" :label="__('Assistive device used')" />
                    <flux:input wire:model.blur="data.ie.walk_distance" :label="__('Can walk (feet / mins)')" />
                    <flux:radio.group wire:model.live="data.ie.walk_with_device" :label="__('Assistive device')" variant="segmented">
                        <flux:radio value="with" :label="__('With')" />
                        <flux:radio value="without" :label="__('Without')" />
                    </flux:radio.group>
                    <flux:input wire:model.blur="data.ie.person_assist" :label="__('Person assist needed')" />
                    <flux:input wire:model.blur="data.ie.stair_flights" :label="__('Flights of stairs / steps')" />
                </div>
            </div>

            <div class="{{ $section }}">
                <div class="grid gap-6 sm:grid-cols-2">
                    <div class="space-y-4">
                        <flux:radio.group wire:model="data.ie.sensory" :label="__('Sensory')" variant="segmented">
                            <flux:radio value="intact" :label="__('Intact')" />
                            <flux:radio value="tingling" :label="__('Tingling / numbness')" />
                        </flux:radio.group>
                        <flux:input wire:model="data.ie.numbness_on" :label="__('Tingling / numbness on')" />
                        <div class="grid grid-cols-[auto_1fr] items-end gap-3">
                            <flux:radio.group wire:model="data.ie.xray" :label="__('X-ray')" variant="segmented">
                                <flux:radio value="Y" label="Y" />
                                <flux:radio value="N" label="N" />
                            </flux:radio.group>
                            <flux:input wire:model="data.ie.xray_findings" :placeholder="__('Findings')" />
                            <flux:radio.group wire:model="data.ie.mri" :label="__('MRI')" variant="segmented">
                                <flux:radio value="Y" label="Y" />
                                <flux:radio value="N" label="N" />
                            </flux:radio.group>
                            <flux:input wire:model="data.ie.mri_findings" :placeholder="__('Findings')" />
                        </div>
                    </div>
                    <div class="space-y-4">
                        <flux:textarea wire:model="data.ie.special_test" rows="2" :label="__('Special test')" />
                        <div class="grid grid-cols-[auto_1fr_1fr] items-end gap-3">
                            <flux:radio.group wire:model.live="data.ie.palpation_sign" :label="__('Palpation')" variant="segmented">
                                <flux:radio value="+" label="+" />
                                <flux:radio value="-" label="-" />
                            </flux:radio.group>
                            <flux:input wire:model.blur="data.ie.palpation_grade" :label="__('Grade')" />
                            <flux:input wire:model.blur="data.ie.tenderness_on" :label="__('TTP on')" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Work status') }}</flux:heading>
                <div class="grid gap-1 sm:grid-cols-4">
                    @foreach ($model::workStatuses() as $option)
                        <flux:field variant="inline">
                            <flux:checkbox wire:model="data.ie.work_status" value="{{ $option }}" />
                            <flux:label>{{ $option }}</flux:label>
                        </flux:field>
                    @endforeach
                </div>
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('ADL functions — patient has difficulty with') }}</flux:heading>
                <flux:field variant="inline">
                    <flux:checkbox wire:model.live="data.ie.adl_wnl" />
                    <flux:label>{{ __('WNL') }}</flux:label>
                </flux:field>
                <div class="grid gap-1 sm:grid-cols-3">
                    @foreach ($model::adlDifficulties() as $option)
                        <flux:field variant="inline">
                            <flux:checkbox wire:model.live="data.ie.adl_difficulties" value="{{ $option }}" />
                            <flux:label>{{ $option }}</flux:label>
                        </flux:field>
                    @endforeach
                </div>
                <flux:input wire:model.blur="data.ie.adl_other" :label="__('Others')" />
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Precautions') }}</flux:heading>
                <div class="grid gap-3 sm:grid-cols-3">
                    @foreach ($model::precautions() as $option)
                        <div class="flex items-center gap-2">
                            <flux:field variant="inline">
                                <flux:checkbox wire:model.live="data.ie.precautions" value="{{ $option }}" />
                                <flux:label>{{ $option }}</flux:label>
                            </flux:field>
                            @if (in_array($option, $model::detailedPrecautions(), true))
                                <flux:input wire:model.blur="data.ie.precaution_details.{{ $option }}" size="sm" :placeholder="__('Type / site')" />
                            @endif
                        </div>
                    @endforeach
                </div>
                <flux:input wire:model.blur="data.ie.precautions_other" :label="__('Others')" />
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Goals') }}</flux:heading>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach ($model::goals() as $key => $label)
                        <div class="grid grid-cols-[auto_1fr] items-center gap-2">
                            <flux:field variant="inline">
                                <flux:checkbox wire:model="data.ie.goals" value="{{ $key }}" />
                                <flux:label>{{ __($label) }}</flux:label>
                            </flux:field>
                            <flux:input wire:model="data.ie.goal_details.{{ $key }}" size="sm" />
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="{{ $section }}">
                @include('partials.outpatient-forms.treatment-plan', ['form' => 'ie'])
            </div>

            <div class="{{ $section }}">
                <flux:radio.group wire:model="data.ie.rehab_potential" :label="__('Rehab potential')" variant="segmented" class="max-w-sm">
                    @foreach ($model::rehabPotentials() as $potential)
                        <flux:radio :value="$potential" :label="$potential" />
                    @endforeach
                </flux:radio.group>
                <div class="grid gap-4 sm:grid-cols-4">
                    <flux:input wire:model="data.ie.rx_expiration_date" type="date" :label="__('Rx expiration date')" />
                    <flux:input wire:model="data.ie.total_authorized_visits" type="number" min="0" :label="__('Total authorized visits')" />
                    <flux:input wire:model="data.ie.frequency" type="number" min="0" :label="__('Authorized x / wk')" />
                    <flux:input wire:model="data.ie.duration" type="number" min="0" :label="__('For wks')" />
                </div>
                <div class="flex items-center gap-2">
                    <flux:text>{{ __('D/C plans') }}: <strong>{{ $model::DISCHARGE_PLAN }}</strong></flux:text>
                    <flux:badge size="sm" icon="lock-closed">{{ __('Fixed') }}</flux:badge>
                </div>
            </div>
        </div>

        @endif

        {{-- Re-evaluation --}}
        @if ($tab === 're')
        <div class="space-y-8">
            <div class="{{ $section }}">
                <flux:input wire:model="data.re.date" type="date" :label="__('Re-evaluation date')" class="max-w-xs" />
                @include('partials.outpatient-forms.progress', ['form' => 're', 'statuses' => $model::reEvaluationStatuses(), 'currentLabel' => 'Current'])
                <flux:radio.group wire:model="data.re.pain_meds" :label="__('Pain — meds')" variant="segmented" class="max-w-xs">
                    <flux:radio value="+" :label="__('+ meds')" />
                    <flux:radio value="-" :label="__('- meds')" />
                </flux:radio.group>
            </div>

            <div class="{{ $section }}">
                <flux:textarea wire:model="data.re.objectives" rows="2" :label="__('Objectives')" />
                <flux:radio.group wire:model="data.re.working" :label="__('Working?')" variant="segmented" class="max-w-md">
                    @foreach ($model::reEvaluationWorkingOptions() as $option)
                        <flux:radio :value="$option" :label="$option" />
                    @endforeach
                </flux:radio.group>
                <flux:textarea wire:model="data.re.comments" rows="3" :label="__('PT comments / notes')" />
                <flux:input wire:model="data.re.precautions" :label="__('Precautions')" :placeholder="$preview->precautionSummary()" :description="__('Leave blank to copy the IE precautions.')" />
            </div>

            <div class="{{ $section }}">
                @include('partials.outpatient-forms.treatment-plan', ['form' => 're'])
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Reason for continued PT visits') }}</flux:heading>
                <flux:input wire:model="data.re.still_complains" :label="__('Patient still c/o')" />
                <flux:input wire:model="data.re.will_benefit" :label="__('Patient will benefit from continued PT treatment to')" />
            </div>

            <div class="{{ $section }}">
                @include('partials.outpatient-forms.recommendation', ['form' => 're'])
            </div>
        </div>

        @endif

        {{-- Discharge summary --}}
        @if ($tab === 'dc')
        <div class="space-y-8">
            <div class="{{ $section }}">
                <flux:input wire:model="data.dc.date" type="date" :label="__('Discharge date')" class="max-w-xs" />
                @include('partials.outpatient-forms.progress', ['form' => 'dc', 'statuses' => $model::dischargeStatuses(), 'currentLabel' => 'End / current'])
                <div class="grid max-w-md grid-cols-2 gap-4">
                    <flux:radio.group wire:model="data.dc.pain_meds_initial" :label="__('Pain meds — initial')" variant="segmented">
                        <flux:radio value="+" label="+" />
                        <flux:radio value="-" label="-" />
                    </flux:radio.group>
                    <flux:radio.group wire:model="data.dc.pain_meds_end" :label="__('Pain meds — end')" variant="segmented">
                        <flux:radio value="+" label="+" />
                        <flux:radio value="-" label="-" />
                    </flux:radio.group>
                </div>
            </div>

            <div class="{{ $section }}">
                <div class="grid gap-4 sm:grid-cols-[auto_1fr] sm:items-end">
                    <flux:radio.group wire:model="data.dc.goals_status" :label="__('Goals / objectives')" variant="segmented">
                        @foreach ($model::goalStatuses() as $status)
                            <flux:radio :value="$status" :label="$status" />
                        @endforeach
                    </flux:radio.group>
                    <flux:input wire:model="data.dc.goals_other" :placeholder="__('Others')" />
                </div>
                <flux:textarea wire:model="data.dc.comments" rows="3" :label="__('PT comments / notes')" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:radio.group wire:model="data.dc.working" :label="__('Working?')" variant="segmented">
                        <flux:radio value="Yes" :label="__('Yes')" />
                        <flux:radio value="No" :label="__('No')" />
                    </flux:radio.group>
                    <flux:radio.group wire:model="data.dc.work_duty" :label="__('Duty')" variant="segmented">
                        @foreach ($model::workDuties() as $duty)
                            <flux:radio :value="$duty" :label="$duty" />
                        @endforeach
                    </flux:radio.group>
                </div>
                <flux:input wire:model="data.dc.still_complains" :label="__('Patient still c/o')" />
                <flux:label>{{ __('Patient will benefit from continued PT treatment to') }}</flux:label>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="data.dc.benefit_decrease" :label="__('Decrease')" />
                    <flux:input wire:model="data.dc.benefit_increase" :label="__('Increase')" />
                </div>
            </div>

            <div class="{{ $section }}">
                <flux:heading size="lg">{{ __('Discharge reason') }}</flux:heading>
                <div class="grid gap-1 sm:grid-cols-2">
                    @foreach ($model::dischargeReasons() as $option)
                        <flux:field variant="inline">
                            <flux:checkbox wire:model="data.dc.discharge_reasons" value="{{ $option }}" />
                            <flux:label>{{ $option }}</flux:label>
                        </flux:field>
                    @endforeach
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:input wire:model="data.dc.noncompliant_reason" :label="__('Non-compliant reason')" />
                    <flux:input wire:model="data.dc.discharge_reasons_other" :label="__('Others')" />
                    <flux:input wire:model="data.dc.surgery_date" type="date" :label="__('Date of surgery')" />
                    <flux:input wire:model="data.dc.surgery_on" :label="__('Surgery on')" />
                </div>
            </div>

            <div class="{{ $section }}">
                @include('partials.outpatient-forms.recommendation', ['form' => 'dc'])
            </div>

            <div class="{{ $section }}">
                @include('partials.outpatient-forms.treatment-plan', ['form' => 'dc'])
                <flux:input wire:model="data.dc.total_visits" type="number" min="0" :label="__('Total # of visits made for this diagnosis')" class="max-w-xs" />
            </div>
        </div>
        @endif

        <div class="flex items-center gap-2">
            <flux:button :href="$outpatientForm ? route('outpatient-forms.show', $outpatientForm) : route('outpatient-forms.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:spacer />
            <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
        </div>
    </form>
</section>
