{{--
<x-proton.row2>
	<x-slot name="slot1">
		<p> Table row content </p>
	</x-slot>
	<x-slot name="slot2">
		<p> Table row content </p>
	</x-slot>
</x-proton.row2>
--}}
<x-tr>
	<x-td colspan="3" style="padding-right: 10px; width: 50%;">
		{{ $slot1 }}
	</x-td>
	<x-td colspan="3" style="padding-left: 10px; width: 50%;">
		{{ $slot2 }}
	</x-td>
</x-tr>
