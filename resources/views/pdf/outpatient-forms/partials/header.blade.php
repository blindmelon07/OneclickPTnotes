@php($date = fn (?string $value): string => $value ? \Illuminate\Support\Carbon::parse($value)->format('m/d/Y') : '')
<table class="title">
    <tr>
        <td class="practice-name">{{ config('notes.letterhead.practice') }} - PHYSICAL THERAPY</td>
        <td class="form-name">{{ strtoupper($title) }}</td>
    </tr>
</table>

<table class="rows">
    <tr>
        <td class="label">Px's Name:</td>
        <td><span class="fill wide">{{ $data['patient_name'] }}</span></td>
        <td class="label">Age:</td>
        <td><span class="fill">{{ $data['age'] }}</span></td>
        <td class="label">DOB:</td>
        <td><span class="fill">{{ $date($data['dob']) }}</span></td>
        <td class="nowrap">
            @foreach (\App\Models\OutpatientForm::hands() as $hand)
                <span class="box {{ $data['hand'] === $hand ? 'on' : '' }}"></span>{{ $hand }}&nbsp;
            @endforeach
        </td>
    </tr>
    <tr>
        <td class="label">Dx:</td>
        <td><span class="fill wide">{{ $data['diagnosis'] }}</span></td>
        <td class="label">DOI:</td>
        <td><span class="fill">{{ $date($data['doi']) }}</span></td>
        <td class="label">DOSx:</td>
        <td colspan="2"><span class="fill">{{ $data['dosx'] }}</span></td>
    </tr>
</table>
