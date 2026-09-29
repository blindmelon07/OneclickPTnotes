@php($model = \App\Models\StagingForm::class)
<h1>TEAM CONFERENCE</h1>
<div style="text-align: right; font-style: italic;">Initial Evaluation</div>

<table class="meta">
    <tr>
        <td class="label">Agency name:</td>
        <td>{{ $data['agency_name'] }}</td>
        <td class="label">MR #:</td>
        <td>{{ $data['mr_number'] }}</td>
    </tr>
    <tr>
        <td class="label">Patient name:</td>
        <td>{{ $data['patient_name'] }}</td>
        <td class="label">Date:</td>
        <td>{{ $data['evaluation_date'] ? \Illuminate\Support\Carbon::parse($data['evaluation_date'])->format('M j, Y') : '' }}</td>
    </tr>
    <tr>
        <td class="label">Service/s:</td>
        <td colspan="3">
            @foreach (['SN', 'PT', 'OT', 'RT', 'MSW', 'Dietician'] as $service)
                <span class="box {{ $service === 'PT' ? 'on' : '' }}"></span>{{ $service }}&nbsp;&nbsp;
            @endforeach
        </td>
    </tr>
</table>

@foreach (array_chunk($model::teamConferenceProblems(), 2, true) as $pair)
    <table class="plain">
        <tr>
            @foreach ($pair as $discipline => $problems)
                <td>
                    <h2>{{ $discipline }}</h2>
                    @include('pdf.staging-forms.partials.checks', [
                        'options' => $problems,
                        'checked' => $discipline === 'Physical therapy / respiratory therapy' ? $model::fixedTeamConferenceProblems() : [],
                        'columns' => 1,
                    ])
                </td>
            @endforeach
        </tr>
    </table>
@endforeach

<h2>Comments / progress</h2>
<div class="value">{{ $data['problems'] }}</div>

<h2>Recommendations</h2>
@include('pdf.staging-forms.partials.checks', ['options' => ['Speech therapist', 'CHHA', 'Respiratory therapist', 'Dietician'], 'checked' => []])

<table class="meta" style="margin-top: 10px;">
    <tr><td class="label">Nursing supervisor:</td><td>______________________________</td><td class="label">RN signature:</td><td>______________________________</td></tr>
    <tr><td class="label"></td><td></td><td class="label">LVN signature:</td><td>______________________________</td></tr>
    <tr>
        <td class="label">Meeting:</td>
        <td><span class="box"></span>Teleconference &nbsp; <span class="box"></span>Actual meeting</td>
        <td class="label">Discipline:</td>
        <td>
            @foreach (['PT', 'OT', 'RT', 'ST'] as $discipline)
                <span class="box {{ $discipline === 'PT' ? 'on' : '' }}"></span>{{ $discipline }}&nbsp;&nbsp;
            @endforeach
        </td>
    </tr>
</table>
