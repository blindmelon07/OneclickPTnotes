@php($model = \App\Models\OutpatientForm::class)
@php($re = $data['re'])
@php($ie = $data['ie'])
@php($box = fn (bool $on): string => '<span class="box '.($on ? 'on' : '').'"></span>')

<table class="progress">
    <tr>
        <th style="width: 18%;">Measure</th>
        <th style="width: 30%;">Current</th>
        <th style="width: 30%;">IE</th>
        <th>Status</th>
    </tr>
    @foreach ($model::progressRows() as $row => $label)
        <tr>
            <td>{!! $box($form->isProgressRowFilled('re', $row)) !!}{{ strtoupper($label) }}{{ $row === 'others' && $re['others_label'] ? ': '.$re['others_label'] : '' }}</td>
            <td>
                @if ($row === 'mmt')
                    Grip: R: <span class="fill">{{ $re['grip']['right'] }}</span>lbs L: <span class="fill">{{ $re['grip']['left'] }}</span>lbs<br>
                @endif
                {!! nl2br(e($re['progress'][$row]['value'])) !!}
                @if ($row === 'pain')
                    &nbsp; {!! $box($re['pain_meds'] === '+') !!}+ / {!! $box($re['pain_meds'] === '-') !!}- MEDS
                @endif
            </td>
            <td>
                {!! nl2br(e($form->baselineFor('re', $row))) !!}
                @if ($row === 'mmt' && (filled($ie['grip']['right']) || filled($ie['grip']['left'])))
                    <br>Grip: R: {{ $ie['grip']['right'] }}lbs L: {{ $ie['grip']['left'] }}lbs
                @endif
            </td>
            <td class="nowrap">
                @foreach ($model::reEvaluationStatuses() as $status)
                    {!! $box($re['progress'][$row]['status'] === $status) !!}{{ $status }}&nbsp;
                @endforeach
            </td>
        </tr>
    @endforeach
</table>

<table class="rows">
    <tr><td class="label">OBJECTIVES:</td><td><span class="fill" style="display: block;">{{ $re['objectives'] }}&nbsp;</span></td></tr>
    <tr>
        <td class="label">WORKING?:</td>
        <td>
            @foreach ($model::reEvaluationWorkingOptions() as $option)
                {!! $box($re['working'] === $option) !!}{{ strtoupper($option) }}&nbsp;
            @endforeach
        </td>
    </tr>
    <tr><td class="label">PT COMMENTS/NOTES:</td><td><div class="value">{{ $re['comments'] }}</div></td></tr>
    <tr><td class="label">PRECAUTION/S:</td><td><span class="fill" style="display: block;">{{ $form->reEvaluationPrecautions() }}&nbsp;</span></td></tr>
</table>

@include('pdf.outpatient-forms.partials.treatment-plan', ['plan' => 're'])

<h2>Reason for continued PT visits</h2>
<table class="rows">
    <tr><td>{!! $box(filled($re['still_complains'])) !!}Patient Still c/o <span class="fill wide">{{ $re['still_complains'] }}</span></td></tr>
    <tr><td>{!! $box(filled($re['will_benefit'])) !!}Patient will benefit from continued PT treatment to <span class="fill wide">{{ $re['will_benefit'] }}</span></td></tr>
</table>

@include('pdf.outpatient-forms.partials.recommendation', ['block' => $re])
