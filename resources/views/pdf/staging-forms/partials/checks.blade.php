{{-- A grid of printed checkboxes: $options, $checked, optional $columns (2) and $suffixes ([option => text]). --}}
@php($columns = $columns ?? 2)
<table class="checks {{ $columns === 3 ? 'three' : '' }}">
    @foreach (array_chunk($options, $columns) as $row)
        <tr>
            @foreach ($row as $option)
                <td>
                    <span class="box {{ in_array($option, $checked, true) ? 'on' : '' }}"></span>
                    {{ $option }}{!! isset($suffixes[$option]) ? ' <span class="fill">'.e($suffixes[$option]).'</span>' : '' !!}
                </td>
            @endforeach
        </tr>
    @endforeach
</table>
