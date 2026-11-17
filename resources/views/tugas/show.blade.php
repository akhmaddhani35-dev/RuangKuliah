<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Detail Tugas</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Mata Kuliah</dt><dd class="font-medium">{{ $tugas->mataKuliah?->nama ?? $tugas->nama_mata_kuliah }}</dd></div>
                <div><dt class="text-sm text-gray-500">Judul</dt><dd class="font-medium">{{ $tugas->judul }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Deskripsi</dt><dd class="whitespace-pre-line font-medium">{{ $tugas->deskripsi }}</dd></div>
                <div><dt class="text-sm text-gray-500">Deadline</dt><dd class="font-medium">{{ $tugas->deadline }}</dd></div>
                <div><dt class="text-sm text-gray-500">Prioritas</dt><dd class="font-medium">{{ $tugas->prioritas }}</dd></div>
                @if ($tugas->file_path)
                    <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Lampiran</dt><dd><a class="font-medium text-indigo-600 hover:underline" href="{{ route('tugas.download', $tugas) }}">{{ $tugas->file_name }}</a></dd></div>
                @endif
            </dl>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('tugas.index') }}" class="py-2 text-sm text-gray-600">Kembali</a><a href="{{ route('tugas.edit', $tugas) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit</a></div>
        </div>
    </div></div>
</x-app-layout>
