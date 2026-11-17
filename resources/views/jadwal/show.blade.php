<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Detail Jadwal</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Mata Kuliah</dt><dd class="font-medium">{{ $jadwal->mataKuliah?->nama ?? $jadwal->nama_mata_kuliah }}</dd></div>
                <div><dt class="text-sm text-gray-500">Hari</dt><dd class="font-medium">{{ $jadwal->hari }}</dd></div>
                <div><dt class="text-sm text-gray-500">Waktu</dt><dd class="font-medium">{{ $jadwal->waktu_mulai }} - {{ $jadwal->waktu_selesai }}</dd></div>
                <div><dt class="text-sm text-gray-500">Ruang</dt><dd class="font-medium">{{ $jadwal->ruang }}</dd></div>
                <div><dt class="text-sm text-gray-500">Dosen</dt><dd class="font-medium">{{ $jadwal->dosen }}</dd></div>
                <div><dt class="text-sm text-gray-500">Mode</dt><dd class="font-medium">{{ $jadwal->mode }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Link Zoom</dt><dd class="font-medium">{{ $jadwal->link_zoom }}</dd></div>
            </dl>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('jadwal.index') }}" class="py-2 text-sm text-gray-600">Kembali</a>@if (auth()->user()->role === 'admin')<a href="{{ route('jadwal.edit', $jadwal) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit</a>@endif</div>
        </div>
    </div></div>
</x-app-layout>
