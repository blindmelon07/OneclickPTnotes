@php($model = \App\Models\StagingForm::class)
<h1>P.T. / O.T. EVALUATION</h1>

<table class="meta">
    <tr>
        <td class="label">Agency name:</td>
        <td>{{ $data['agency_name'] }}</td>
        <td class="label">BP / HR:</td>
        <td>{{ $data['blood_pressure'] }} &nbsp; / &nbsp; {{ $data['heart_rate'] }}</td>
    </tr>
    <tr>
        <td class="label">Patient name:</td>
        <td>{{ $data['patient_name'] }}</td>
        <td class="label">MR #:</td>
        <td>{{ $data['mr_number'] }}</td>
    </tr>
    <tr>
        <td class="label">Diagnosis:</td>
        <td colspan="3">{{ $data['diagnosis'] }}</td>
    </tr>
</table>

<table class="plain">
    <tr>
        <td>
            <h2>Problems identified</h2>
            <div class="value">{{ $data['problems'] }}</div>
        </td>
        <td>
            <h2>Short term goal (4-8 weeks)</h2>
            <div class="value">{{ $data['short_term_goals'] }}</div>
        </td>
    </tr>
</table>

<h2>Functional level</h2>
<div class="legend">LEGEND: {{ implode(' - ', $model::assistLevels()) }}</div>
<table class="levels">
    @foreach (collect($model::functionalActivities())->groupBy('group', preserveKeys: true) as $group => $activities)
        <tr>
            <td class="group">{{ strtoupper($group) }}:</td>
            <td>
                @foreach ($activities as $key => $activity)
                    {{ $activity['label'] }}: <span class="fill">{{ $form->functionalLevelFor($key) }}</span>&nbsp;&nbsp;
                @endforeach
            </td>
        </tr>
    @endforeach
</table>

<table class="plain">
    <tr>
        <td>
            <h2>Gait description</h2>
            <table class="meta">
                @foreach ($model::gaitFields() as $key => $label)
                    <tr>
                        <td class="label">{{ $label }}:</td>
                        <td>{{ $form->gaitValue($key) }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
        <td>
            <h2>Functional limitations</h2>
            @include('pdf.staging-forms.partials.checks', ['options' => $model::functionalLimitations(), 'checked' => $data['functional_limitations']])
            @if ($data['functional_limitations_other'])
                <div>Others: {{ $data['functional_limitations_other'] }}</div>
            @endif
        </td>
    </tr>
</table>

<table class="plain">
    <tr>
        <td>
            <h2>Homebound reasons</h2>
            @include('pdf.staging-forms.partials.checks', [
                'options' => $model::homeboundReasons(),
                'checked' => $data['homebound_reasons'],
                'columns' => 1,
                'suffixes' => ['Cannot ambulate >' => $form->cannotAmbulateDistance().' feet / mins'],
            ])
        </td>
        <td>
            <h2>Cognition</h2>
            @include('pdf.staging-forms.partials.checks', ['options' => $model::cognitionOptions(), 'checked' => $data['cognition']])
            @if ($data['cognition_other'])
                <div>Others: {{ $data['cognition_other'] }}</div>
            @endif

            <h2>Balance / tolerance</h2>
            <table class="grid">
                <tr><th></th><th>Static</th><th>Dynamic</th></tr>
                <tr><td>Sitting</td><td>{{ $form->balanceGrade('sitting', 'static') }}</td><td>{{ $form->balanceGrade('sitting', 'dynamic') }}</td></tr>
                <tr><td>Standing</td><td>{{ $form->balanceGrade('standing', 'static') }}</td><td>{{ $form->balanceGrade('standing', 'dynamic') }}</td></tr>
            </table>

            <table class="meta">
                <tr><td class="label">Living situation:</td><td>{{ $data['living_situation'] }}</td></tr>
                <tr><td class="label">Precautions:</td><td>{{ $form->precautionSummary() }}</td></tr>
                <tr>
                    <td class="label">Rehab potential:</td>
                    <td>
                        @foreach ($model::rehabPotentials() as $potential)
                            <span class="box {{ $data['rehab_potential'] === $potential ? 'on' : '' }}"></span>{{ $potential }}&nbsp;&nbsp;
                        @endforeach
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="plain">
    <tr>
        <td>
            <h2>ROM: all joints are WFL / WNL except for</h2>
            <div class="value">{{ $data['rom_exceptions'] }}</div>
        </td>
        <td>
            <h2>MMT: all paraspinal &amp; both UE/LE are WFL except for</h2>
            <div class="value">{{ $data['mmt_exceptions'] }}</div>
        </td>
    </tr>
</table>

<h2>Activities permitted</h2>
@include('pdf.staging-forms.partials.checks', ['options' => $model::activitiesPermitted(), 'checked' => $data['activities_permitted'], 'columns' => 3])

<table class="plain">
    <tr>
        <td><h2>Wheelchair skills</h2><div class="value">{{ $data['wheelchair_skills'] }}</div></td>
        <td><h2>Prior level of function</h2><div class="value">{{ $data['prior_level_of_function'] }}</div></td>
    </tr>
    <tr>
        <td><h2>Palpation</h2><div class="value">{{ $data['palpation'] }}</div></td>
        <td><h2>Pain (location / intensity)</h2><div class="value">{{ $data['pain'] }}</div></td>
    </tr>
    <tr>
        <td><h2>DME needed</h2><div class="value">{{ $data['dme_needed'] }}</div></td>
        <td><h2>Comments</h2><div class="value">{{ $data['comments'] }}</div></td>
    </tr>
</table>

<table class="meta">
    <tr>
        <td class="label">PT frequency:</td>
        <td>{{ $data['pt_frequency'] }}</td>
        <td class="label">Discharge plans:</td>
        <td>{{ $data['discharge_plans'] }}</td>
    </tr>
</table>

<h2>Plan of care</h2>
<div class="value">{{ $data['plan_of_care'] }}</div>

<table class="meta">
    <tr>
        <td class="label">Time in:</td>
        <td>{{ $data['time_in'] }}</td>
        <td class="label">Time out:</td>
        <td>{{ $data['time_out'] }}</td>
    </tr>
</table>
