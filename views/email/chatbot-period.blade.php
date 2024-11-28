<x-email title="{{ $title }}" locale="en">
	<x-slot name="style">
		<style>
			.proton-table tr td {
				padding: 0px 40px;
			}
		</style>
	</x-slot>
	
	<x-title value="{{$title}}" />
	
	<x-row>
		<x-margin />
		<center>
			<p>{{$count}} chat(s) created {{$period}} with the {{$name}}</p>
		</center>

		
	</x-row>

	<x-row>
		<x-button url="{{$link}}">
			See Chat History
		</x-button>
	</x-row>

	<x-row>
		<center>
			<small>
				If you do not want to receive this e-mail, you can switch it off in the <x-tags.a url="{{$settings}}">chatbot settings</x-tags.a>.
			</small>
		</center>

		<x-margin />
	</x-row>
</x-email>
