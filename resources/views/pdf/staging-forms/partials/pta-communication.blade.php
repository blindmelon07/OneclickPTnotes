@php($model = \App\Models\StagingForm::class)
<h1>COMMUNICATION NOTE - PTA INSTRUCTIONS</h1>

<table class="meta">
    <tr>
        <td class="label">Date:</td>
        <td>{{ $data['evaluation_date'] ? \Illuminate\Support\Carbon::parse($data['evaluation_date'])->format('M j, Y') : '' }}</td>
        <td class="label">MR #:</td>
        <td>{{ $data['mr_number'] }}</td>
    </tr>
    <tr>
        <td class="label">To:</td>
        <td colspan="3">{{ $data['pta_name'] }}{{ $data['pta_name'] ? ', PTA' : '' }}</td>
    </tr>
    <tr>
        <td class="label">Agency name:</td>
        <td colspan="3">{{ $data['agency_name'] }}</td>
    </tr>
    <tr>
        <td class="label">Patient name:</td>
        <td colspan="3">{{ $data['patient_name'] }}</td>
    </tr>
    <tr>
        <td class="label">Address:</td>
        <td>{{ $data['patient_address'] }}</td>
        <td class="label">Tel #:</td>
        <td>{{ $data['patient_phone'] }}</td>
    </tr>
</table>

<div class="value">{{ $data['problems'] }}</div>

<p>
    Please see PX starting <span class="fill">{{ $data['pta_start_week'] }}</span> week.
    Freq. of <span class="fill">{{ $data['pta_frequency'] }}</span>.
    I will see PX for DC after your visits.
</p>
<p><strong>Total of {{ $form->totalPtaVisits() }} visits for you.</strong></p>

<table class="grid">
    <tr><th>Week of</th><th># of visits</th><th></th></tr>
    @foreach ($form->ptaScheduleRows() as $row)
        <tr>
            <td>{{ $row['week_of'] }}</td>
            <td>{{ $row['visits'] }}</td>
            <td>Follow up visit/s</td>
        </tr>
    @endforeach
</table>

<h2>Plan of care</h2>
<ol class="list">
    @foreach ($model::ptaPlanOfCare() as $item)
        <li>{{ strtoupper($item) }}</li>
    @endforeach
</ol>

<table class="meta">
    <tr><td class="label">Precaution:</td><td>{{ $data['precautions'] }}</td></tr>
</table>

<h2>Goals of PX</h2>
<ol class="list">
    @foreach ($model::ptaGoals() as $goal)
        <li>{{ strtoupper($goal) }}</li>
    @endforeach
</ol>

<p>Call or text me if you are on your last visit with the patient so that I can discharge him/her after you see him/her. I will fax this note along with the evaluation to the agency.</p>
<p><strong>Please call or text me as soon as you receive this note.</strong></p>
<p>Thanks!</p>
