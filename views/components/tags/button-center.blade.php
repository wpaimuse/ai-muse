<x-tags.center>
    <a 
        {{ $attributes->merge(['class' => 'proton-tags-button']) }}
        href="{{ $url  }}" 
        target="_blank"
    >{{ $slot }}</a>
</x-tags.center>