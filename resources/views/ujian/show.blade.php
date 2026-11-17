<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800 dark:text-white">Detail Agenda Ujian</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <article class="rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800">
            <span class="rounded-full bg-violet-100 px-3 py-1 text-xs font-bold text-violet-700 dark:bg-violet-900/50 dark:text-violet-200">{{ $agendaUjian->jenis }}</span>
            <h3 class="mt-4 text-xl font-semibold text-gray-900 dark:text-white">{{ $agendaUjian->mataKuliah?->nama }}</h3>
            <dl class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Tanggal</dt><dd class="font-medium">{{ \Illuminate\Support\Carbon::parse($agendaUjian->tanggal)->format('d M Y') }}</dd></div>
                <div><dt class="text-sm text-gray-500">Waktu</dt><dd class="font-medium">{{ substr($agendaUjian->waktu_mulai, 0, 5) }} - {{ substr($agendaUjian->waktu_selesai, 0, 5) }}</dd></div>
                <div><dt class="text-sm text-gray-500">Mode</dt><dd class="font-medium">{{ $agendaUjian->mode }}</dd></div>
                @if ($agendaUjian->ruang)<div><dt class="text-sm text-gray-500">Ruang</dt><dd class="font-medium">{{ $agendaUjian->ruang }}</dd></div>@endif
                @if ($agendaUjian->catatan)<div class="sm:col-span-2"><dt class="text-sm text-gray-500">Catatan</dt><dd class="whitespace-pre-line font-medium">{{ $agendaUjian->catatan }}</dd></div>@endif
                @if ($agendaUjian->link_zoom)<div class="sm:col-span-2"><dt class="text-sm text-gray-500">Link Zoom</dt><dd><a href="{{ $agendaUjian->link_zoom }}" target="_blank" rel="noopener noreferrer" class="break-all font-medium text-indigo-600 underline">{{ $agendaUjian->link_zoom }}</a></dd></div>@endif
            </dl>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('ujian.index') }}" class="py-2 text-sm text-gray-600">Kembali</a>@if (auth()->user()->role === 'admin')<a href="{{ route('ujian.edit', $agendaUjian) }}" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit</a>@endif</div>
        </article>
    </div></div>
</x-app-layout>
