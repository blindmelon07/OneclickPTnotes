@php($model = \App\Models\OutpatientForm::class)
@php($dc = $data['dc'])
@php($ie = $data['ie'])
@php($date = fn (?string $value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('m/d/Y') : '')
@php($box = fn (bool $on): string => '<span class="box '.($on ? 'on' : '').'"></span>')
@php($meds = fn (string $value): string => $box($value === '+').'+ / '.$box($value === '-').'- MEDS')

<table class="progress">
    <tr>
        <th style="width: 18%;">Measure</th>
        <th style="width: 28%;">Initial / baseline values</th>
        <th style="width: 26%;">Status</th>
        <th>End / current values</th>
    </tr>
    @foreach ($model::progressRows() as $row => $label)
        <tr>
            <td>{!! $box($form->isProgressRowFilled('dc', $row)) !!}{{ strtoupper($label) }}{{ $row === 'others' && $dc['others_label'] ? ': '.$dc['others_label'] : '' }}</td>
            <td>
                @if ($row === 'mmt')
                    Grip: R: <span class="fill">{{ $ie['grip']['right'] }}</span>lbs L: <span class="fill">{{ $ie['grip']['left'] }}</span>lbs<br>
                @endif
                {!! nl2br(e($form->baselineFor('dc', $row))) !!}
                @if ($row === 'pain')
                    &nbsp; {!! $meds($dc['pain_meds_initial']) !!}
                @endif
            </td>
            <td class="nowrap">
                @foreach ($model::dischargeStatuses() as $status)
                    {!! $box($dc['progress'][$row]['status'] === $status) !!}{{ $status }}&nbsp;
                @endforeach
            </td>
            <td>
                @if ($row === 'mmt' && (filled($dc['grip']['right']) || filled($dc['grip']['left'])))
                    Grip: R: {{ $dc['grip']['right'] }}lbs L: {{ $dc['grip']['left'] }}lbs<br>
                @endif
                {!! nl2br(e($dc['progress'][$row]['value'])) !!}
                @if ($row === 'pain')
                    &nbsp; {!! $meds($dc['pain_meds_end']) !!}
                @endif
            </td>
        </tr>
    @endforeach
</table>

<table class="rows">
    <tr>
        <td class="label">Goals / Objectives:</td>
        <td>
            @foreach ($model::goalStatuses() as $status)
                {!! $box($dc['goals_status'] === $status) !!}{{ $status }}&nbsp;
            @endforeach
            <span class="fill">{{ $dc['goals_other'] }}</span>
        </td>
    </tr>
    <tr><td class="label">PT COMMENTS/NOTES:</td><td><div class="value">{{ $dc['comments'] }}</div></td></tr>
    <tr>
        <td class="label">WORKING?:</td>
        <td>
            {!! $box($dc['working'] === 'Yes') !!}YES {!! $box($dc['working'] === 'No') !!}NO &nbsp;&nbsp;
            @foreach ($model::workDuties() as $duty)
                {!! $box($dc['work_duty'] === $duty) !!}{{ strtoupper($duty) }}&nbsp;
            @endforeach
        </td>
    </tr>
</table>

<table class="rows">
    <tr><td colspan="2">{!! $box(filled($dc['still_complains'])) !!}Patient Still c/o <span class="fill wide">{{ $dc['still_complains'] }}</span></td></tr>
    <tr>
        <td colspan="2">
            {!! $box(filled($dc['benefit_decrease']) || filled($dc['benefit_increase'])) !!}Patient will benefit from continued PT treatment to
            {!! $box(filled($dc['benefit_decrease'])) !!}DECREASE <span class="fill">{{ $dc['benefit_decrease'] }}</span>
            {!! $box(filled($dc['benefit_increase'])) !!}INCREASE <span class="fill">{{ $dc['benefit_increase'] }}</span>
        </td>
    </tr>
</table>

<table class="checks">
    @foreach (array_chunk($model::dischargeReasons(), 2) as $row)
        <tr>
            @foreach ($row as $reason)
                <td>
                    {!! $box(in_array($reason, $dc['discharge_reasons'], true)) !!}{{ $reason }}
                    @if ($reason === 'Patient is non-compliant')
                        Reason: <span class="fill">{{ $dc['noncompliant_reason'] }}</span>
                    @elseif ($reason === 'Others')
                        <span class="fill">{{ $dc['discharge_reasons_other'] }}</span>
                    @endif
                </td>
            @endforeach
        </tr>
    @endforeach
</table>
<div>Date of surgery: <span class="fill">{{ $date($dc['surgery_date']) }}</span> &nbsp; Surgery on: <span class="fill wide">{{ $dc['surgery_on'] }}</span></div>

@include('pdf.outpatient-forms.partials.recommendation', ['block' => $dc])

@include('pdf.outpatient-forms.partials.treatment-plan', ['plan' => 'dc'])

<div><strong>TOTAL # OF VISITS MADE FOR THIS DIAGNOSIS:</strong> <span class="fill">{{ $dc['total_visits'] }}</span></div>
