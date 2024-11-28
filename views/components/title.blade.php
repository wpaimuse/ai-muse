@push('styles')
  <style>
    .proton-table tr.title {
			background: #2271b1;
			color: #ffffff;
		}

		.proton-table tr.title td {
			padding: 15px 25px;
		}

		.proton-table tr.title h3 {
			color: #ffffff;
			margin: 0 !important;
		}
  </style>
@endpush

<x-tr class="title">
  <x-td colspan="10">
    <h3>{{$value}}</h3>
  </x-td>
</x-tr>
