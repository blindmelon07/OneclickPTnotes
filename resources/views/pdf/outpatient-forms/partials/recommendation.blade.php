{{-- The PT recommendation for $block (the RE or DC data). --}}
@php($model = \App\Models\OutpatientForm::class)
@php($checked = fn (string $value): string => $block['recommendation'] === $value ? 'on' : '')

<h2>PT recommendations</h2>
<table class="rows">
    <tr><td><span class="box {{ $checked($model::RECOMMENDATION_CONTINUE) }}"></span>Continue with the remaining Physical Therapy visit. <span class="fill">{{ $block['visits_left'] }}</span> visits left</td></tr>
    <tr><td><span class="box {{ $checked($model::RECOMMENDATION_DISCHARGE) }}"></span>Recommend Discharge of Patient due to <span class="fill wide">{{ $block['discharge_reason'] }}</span></td></tr>
    <tr><td><span class="box {{ $checked($model::RECOMMENDATION_CONTINUED_THERAPY) }}"></span>Recommend Continued Therapy <span class="fill">{{ $block['continued_frequency'] }}</span> x/wk for <span class="fill">{{ $block['continued_duration'] }}</span> wks. (needs new doctor's prescription)</td></tr>
    <tr><td><span class="box {{ $checked($model::RECOMMENDATION_AWAITING) }}"></span>Awaiting your recommendation.</td></tr>
</table>
