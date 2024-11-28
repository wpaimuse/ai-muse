<x-email title="{{ $title }}" locale="en">
	@push('styles')
		<style>
			.proton-table tr td {
				padding: 0px 20px;
			}

      .proton-table td.message {
        padding-left: 40px;
        padding-right: 40px;
      }

      .proton-table td.message p {
        font-size: 16px;
      }

      .proton-table td.message .user {
        background-color: #f0f0f0;
        border-radius: 10px;
        padding: 10px;
        margin: 10px;
        margin-left: 0px;
        display: inline-block;
        float: left;
        font-size: 16px;
      }

      .proton-table td.message .model {
        background-color: #f0f0f0;
        border-radius: 10px;
        padding: 10px;
        margin-top: 10px;
        display: inline-block;
       
      }

      .proton-table td.message * {
        max-width: 75vw;
      }

      .proton-table td.message .message-bubble {
        overflow: hidden;
      }

      .proton-table td.message pre {
        display: inline-block;
        font-size: small;
        background-color: #FFFFFF;
        border-radius: 10px;
        padding: 10px;
        max-width: 70vw;
      }

      .proton-table td.message pre code {
        max-width: 70vw;
        white-space: break-spaces;
      }

      .hidden {
        width: 0px;
        height: 0px;
        pointer-events: none;
        color: transparent;
        position: absolute;
        left: -9999px;
        z-index: -500;
      }

      .chat-header {
        padding: 10px;
        margin: 10px;
      }
		</style>
	@endpush
	
	<x-title value="{{$title}}" />

  <x-row>
    <div class="chat-header">
      @if ($visitor['name'])
      <div>
        Name: {{ $visitor['name'] }}
      </div>
    @endif
    @if ($visitor['email'])
      <div>
        Email: {{ $visitor['email'] }}
      </div>
    @endif
    </div>
  </x-row>

  <x-row></x-row>

  @foreach ($messages as $message)
  <x-row class="message">
    <x-tags.div class="{{ $message['role'] }} message-bubble">
      @if ($message['role'] == 'user')
       <div>{{ $visitor['name'] ?? 'User' }}:</div>
      @else
        {{ $bot }}:
      @endif
      {!! $message['content'] !!}
      <div class="hidden"></div>
      @if ($message['role'] == 'user')
        <p></p>
      @endif
    </x-tags.div>
  </x-row>
  @endforeach

  <x-row>
    <x-margin />
  </x-row>
</x-email>
