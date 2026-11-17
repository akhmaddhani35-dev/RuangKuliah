@props(['class' => 'size-11 rounded-2xl'])

<span {{ $attributes->merge(['class' => "grid shrink-0 place-items-center bg-gradient-to-br from-sky-500 via-blue-600 to-indigo-700 text-white shadow-md shadow-blue-950/20 {$class}"]) }} aria-hidden="true">
    <svg viewBox="0 0 24 24" class="size-6" fill="none">
        <path d="m2.5 9 9.5-5 9.5 5-9.5 5-9.5-5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round" />
        <path d="M6.5 11.2v4.3c3.4 2.4 7.6 2.4 11 0v-4.3M21.5 9v5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
    </svg>
</span>
