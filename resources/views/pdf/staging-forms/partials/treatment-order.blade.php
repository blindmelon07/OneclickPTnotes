<h1>P.T. / O.T. TREATMENT ORDER</h1>

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
        <td>____________</td>
    </tr>
</table>

<div>
    <span class="box on"></span> New order &nbsp;&nbsp;&nbsp;
    <span class="box"></span> Additional order
</div>

<h2>Problem/s</h2>
<div class="value">{{ $data['problems'] }}</div>

<h2>Short term (4-8 weeks) goal/s for this certification period</h2>
<div class="value">{{ $data['short_term_goals'] }}</div>

<h2>Plan of care</h2>
<div class="value">{{ $data['plan_of_care'] }}</div>

<h2>DME/s needed</h2>
<div class="value">{{ $data['dme_needed'] }}</div>

<h2>Comments</h2>
<div class="value">{{ $data['comments'] }}</div>

<table class="meta" style="margin-top: 10px;">
    <tr><td class="label">PT frequency:</td><td colspan="3">{{ $data['pt_frequency'] }}</td></tr>
    <tr><td class="label">Physician name:</td><td colspan="3">_____________________________________________</td></tr>
    <tr>
        <td class="label">Physician signature:</td>
        <td>_____________________________________________</td>
        <td class="label">Date:</td>
        <td>____________</td>
    </tr>
</table>
