{{--
<x-proton.row3>
	<x-slot name="slot1">
		<p> Table row content </p>
	</x-slot>
	<x-slot name="slot2">
		<p> Table row content </p>
	</x-slot>
	<x-slot name="slot3">
		<p> Table row content </p>
	</x-slot>
</x-proton.row3>
--}}
<x-tr>
	<x-td colspan="2" style="padding-right: 5px;  width: 33%;">
		{{ $slot1 }}
	</x-td>
	<x-td colspan="2" style="padding-left: 5px; padding-right: 5px;  width: 28%;">
		{{ $slot2 }}
	</x-td>
	<x-td colspan="2" style="padding-left: 5px;  width: 33%;">
		{{ $slot3 }}
	</x-td>
</x-tr>
