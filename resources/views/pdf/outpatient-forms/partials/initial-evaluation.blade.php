@php($model = \App\Models\OutpatientForm::class)
@php($ie = $data['ie'])
@php($date = fn (?string $value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('m/d/Y') : '')
@php($box = fn (bool $on): string => '<span class="box '.($on ? 'on' : '').'"></span>')

<table class="rows">
    <tr><td class="label">Hx:</td><td><div class="value">{{ $ie['history'] }}</div></td></tr>
    <tr><td class="label">Chief Complaint(s):</td><td><div class="value">{{ $ie['chief_complaints'] }}</div></td></tr>
    <tr><td class="label">ROM:<div class="small" style="font-weight: normal;">(measured in degrees)</div></td><td><div class="value">{{ $ie['rom'] }}</div></td></tr>
    <tr><td class="label">MMT:</td><td><div class="value">{{ $ie['mmt'] }}</div></td></tr>
</table>

<table class="rows">
    <tr>
        <td class="label">GRIP:</td>
        <td>(R) <span class="fill">{{ $ie['grip']['right'] }}</span> LBS &nbsp; (L) <span class="fill">{{ $ie['grip']['left'] }}</span> LBS</td>
    </tr>
    @foreach (['right' => 'RIGHT', 'left' => 'LEFT'] as $side => $label)
        <tr>
            <td class="label">PINCH ({{ $label }}):</td>
            <td>
                @foreach (['ti' => 'T/I', 'tm' => 'T/M', 'tr' => 'T/R', 'tl' => 'T/L'] as $finger => $fingerLabel)
                    {{ $fingerLabel }} <span class="fill">{{ $ie['pinch'][$side][$finger] }}</span> LBS&nbsp;&nbsp;
                @endforeach
            </td>
        </tr>
    @endforeach
</table>

<h2>Gait / stair / step tolerance</h2>
<table class="rows">
    <tr>
        <td>
            Assistive device used: <span class="fill">{{ $ie['assistive_device'] }}</span>
            &nbsp; {!! $box($ie['gait_wnl']) !!}WNL
        </td>
    </tr>
    <tr>
        <td>
            Px can walk <span class="fill">{{ $ie['walk_distance'] }}</span> feet/mins
            {!! $box($ie['walk_with_device'] === 'with') !!}with / {!! $box($ie['walk_with_device'] === 'without') !!}without use of an assistive device.
            <span class="fill">{{ $ie['person_assist'] }}</span> person assist needed.
            Px can manage <span class="fill">{{ $ie['stair_flights'] }}</span> flight of stairs/steps.
        </td>
    </tr>
</table>

<table class="columns">
    <tr>
        <td style="width: 22%;">
            <h2>Sensory</h2>
            <div>{!! $box($ie['sensory'] === 'intact') !!}Intact</div>
            <div>{!! $box($ie['sensory'] === 'tingling') !!}Tingling / Numbness on:</div>
            <div><span class="fill">{{ $ie['numbness_on'] }}</span></div>
            <div style="margin-top: 6px;">XRAY: {!! $box($ie['xray'] === 'Y') !!}Y {!! $box($ie['xray'] === 'N') !!}N</div>
            <div><span class="fill">{{ $ie['xray_findings'] }}</span></div>
            <div style="margin-top: 6px;">MRI: {!! $box($ie['mri'] === 'Y') !!}Y {!! $box($ie['mri'] === 'N') !!}N</div>
            <div><span class="fill">{{ $ie['mri_findings'] }}</span></div>
        </td>
        <td style="width: 28%;">
            <h2>Special test</h2>
            <div class="value">{{ $ie['special_test'] }}</div>
            <div style="margin-top: 6px;">
                <strong>Palpation:</strong>
                {!! $box($ie['palpation_sign'] === '+') !!}+ {!! $box($ie['palpation_sign'] === '-') !!}-
                GRADE <span class="fill">{{ $ie['palpation_grade'] }}</span>
                TTP ON <span class="fill">{{ $ie['tenderness_on'] }}</span>
            </div>
            <h2>Work status</h2>
            <table class="checks">
                @foreach (array_chunk($model::workStatuses(), 2) as $row)
                    <tr>
                        @foreach ($row as $status)
                            <td>{!! $box(in_array($status, $ie['work_status'], true)) !!}{{ $status }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </table>
        </td>
        <td style="width: 28%;">
            <h2>ADL functions</h2>
            <div>px has difficulty with: {!! $box($ie['adl_wnl']) !!}WNL</div>
            @foreach ($model::adlDifficulties() as $difficulty)
                <div>{!! $box(in_array($difficulty, $ie['adl_difficulties'], true)) !!}{{ $difficulty }}</div>
            @endforeach
            <div>others: <span class="fill">{{ $ie['adl_other'] }}</span></div>
        </td>
        <td style="width: 22%;">
            <h2>Precaution</h2>
            @foreach ($model::precautions() as $precaution)
                <div>
                    {!! $box(in_array($precaution, $ie['precautions'], true)) !!}{{ strtoupper($precaution) }}
                    @if (in_array($precaution, $model::detailedPrecautions(), true))
                        <span class="fill">{{ $ie['precaution_details'][$precaution] }}</span>
                    @endif
                </div>
            @endforeach
            <div>OTHERS: <span class="fill">{{ $ie['precautions_other'] }}</span></div>
        </td>
    </tr>
</table>

<h2>Goal/s</h2>
<table class="checks">
    @foreach (array_chunk($model::goals(), 2, preserve_keys: true) as $row)
        <tr>
            @foreach ($row as $key => $label)
                <td>{!! $box(in_array($key, $ie['goals'], true)) !!}{{ $label }} <span class="fill">{{ $ie['goal_details'][$key] }}</span></td>
            @endforeach
        </tr>
    @endforeach
</table>

@include('pdf.outpatient-forms.partials.treatment-plan', ['plan' => 'ie'])

<table class="rows">
    <tr>
        <td>
            <strong>Rehab Potential:</strong>
            @foreach ($model::rehabPotentials() as $potential)
                {!! $box($ie['rehab_potential'] === $potential) !!}{{ $potential }}&nbsp;
            @endforeach
        </td>
        <td><strong>D/C Plans:</strong> {{ $model::DISCHARGE_PLAN }}</td>
    </tr>
    <tr>
        <td><strong>Authorized Frequency &amp; Duration:</strong> <span class="fill">{{ $ie['frequency'] }}</span> x/wk / <span class="fill">{{ $ie['duration'] }}</span> wk</td>
        <td>Rx expiration date: <span class="fill">{{ $date($ie['rx_expiration_date']) }}</span> &nbsp; Total authorized visits: <span class="fill">{{ $ie['total_authorized_visits'] }}</span></td>
    </tr>
</table>
