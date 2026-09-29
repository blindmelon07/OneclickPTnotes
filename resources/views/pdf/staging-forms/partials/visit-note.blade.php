@php($model = \App\Models\StagingForm::class)
<h1>P.T. / O.T. VISIT NOTE</h1>

<table class="meta">
    <tr>
        <td class="label">Patient name:</td>
        <td>{{ $data['patient_name'] }}</td>
        <td class="label">Agency name:</td>
        <td>{{ $data['agency_name'] }}</td>
    </tr>
</table>

<h2>Homebound reasons</h2>
@include('pdf.staging-forms.partials.checks', [
    'options' => $model::homeboundReasons(),
    'checked' => $data['homebound_reasons'],
    'suffixes' => ['Cannot ambulate >' => $form->cannotAmbulateDistance().' feet / mins'],
])

<h2>Services provided</h2>
@include('pdf.staging-forms.partials.checks', ['options' => $model::visitServices(), 'checked' => $model::fixedVisitServices()])

<h2>Observations &amp; instructions during this visit</h2>
<div class="small">(Specific treatments given, patient's reaction and comments as well as specific improvements and achievements)</div>
<div class="value">{{ $data['problems'] }}</div>

<table class="meta">
    <tr><td class="label">Progress towards goal:</td><td>{{ $data['short_term_goals'] }}</td></tr>
    <tr><td class="label">Plan for next visit:</td><td>{{ $data['plan_of_care'] }}</td></tr>
    <tr><td class="label">Discharge instruction (on last visit):</td><td>{{ $data['discharge_plans'] }}</td></tr>
    <tr><td class="label">Pain (location / intensity):</td><td>{{ $data['pain'] }}</td></tr>
    <tr>
        <td class="label">Time in / out:</td>
        <td>{{ $data['time_in'] }} &nbsp; / &nbsp; {{ $data['time_out'] }}</td>
    </tr>
</table>
