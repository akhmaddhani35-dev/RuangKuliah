<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Detail Materi</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl space-y-4 px-4 sm:px-6 lg:px-8">
        <div class="rounded-lg bg-white p-6 shadow-sm">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div><dt class="text-sm text-gray-500">Mata Kuliah</dt><dd class="font-medium">{{ $materi->mataKuliah?->nama ?? $materi->nama_mata_kuliah }}</dd></div>
                <div><dt class="text-sm text-gray-500">Judul</dt><dd class="font-medium">{{ $materi->judul }}</dd></div>
                <div><dt class="text-sm text-gray-500">Pertemuan</dt><dd class="font-medium">{{ $materi->pertemuan }}</dd></div>
                <div><dt class="text-sm text-gray-500">Tipe</dt><dd class="font-medium">{{ $materi->tipe }}</dd></div>
                <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Detail</dt><dd class="break-words font-medium">{{ $materi->detail }}</dd></div>
                @if ($materi->file_path)
                    <div class="sm:col-span-2"><dt class="text-sm text-gray-500">Lampiran</dt><dd><a class="font-medium text-indigo-600 hover:underline" href="{{ route('materi.download', $materi) }}">{{ $materi->file_name }}</a></dd></div>
                @endif
            </dl>
            <div class="mt-6 flex justify-end gap-3"><a href="{{ route('materi.index') }}" class="py-2 text-sm text-gray-600">Kembali</a><a href="{{ route('materi.edit', $materi) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Edit</a></div>
        </div>
    </div></div>
</x-app-layout>
