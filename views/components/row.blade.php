{{--
<x-proton.row colspan="10">
	<p> Table row content </p>
</x-proton.row>
--}}
<x-tr>
	<x-td class="{{ $class ?? '' }}" colspan="{{ $colspan ?? 10 }}" style="{{ $style ?? '' }}">
		{{ $slot }}
	</x-td>
</x-tr>
