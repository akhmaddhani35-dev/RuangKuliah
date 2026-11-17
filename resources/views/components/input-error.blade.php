@props(['messages'])

@if ($messages)
    <ul role="alert" {{ $attributes->merge(['class' => 'mt-2 space-y-1 text-sm font-medium text-rose-700']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
