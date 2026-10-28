{{-- The treatment plan for $plan ('ie' / 're' / 'dc'), each line resolved through what it copies. --}}
<h2>Treatment plan <span class="small" style="text-transform: none; font-weight: normal;">(see also separate plan of care sheet for itemized/detailed info on treatment plan)</span></h2>
<table class="rows">
    @foreach (\App\Models\OutpatientForm::treatmentPlanItems() as $key => $label)
        @if ($key === 'home_program')
            <tr><td class="label" colspan="2">{{ strtoupper($label) }}:</td></tr>
            <tr><td colspan="2"><span class="fill" style="display: block;">{{ $form->planFor($plan, $key) }}&nbsp;</span></td></tr>
        @else
            <tr>
                <td class="label" style="width: 220px;">{{ strtoupper($label) }}:</td>
                <td><span class="fill" style="display: block;">{{ $form->planFor($plan, $key) }}&nbsp;</span></td>
            </tr>
        @endif
    @endforeach
</table>
