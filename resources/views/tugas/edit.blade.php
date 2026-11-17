<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Edit Tugas</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form action="{{ route('tugas.update', $tugas) }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800 sm:p-8">
            @csrf @method('PUT')
            <div>
                <label for="id_mata_kuliah" class="block text-sm font-medium text-gray-700">Mata Kuliah</label>
                <select id="id_mata_kuliah" name="id_mata_kuliah" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih mata kuliah</option>
                    @foreach ($mata_kuliahs as $mataKuliah)<option value="{{ $mataKuliah->id }}" @selected(old('id_mata_kuliah', $tugas->id_mata_kuliah) == $mataKuliah->id)>{{ $mataKuliah->nama }}</option>@endforeach
                </select>
                @error('id_mata_kuliah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700">Judul</label>
                <input id="judul" name="judul" type="text" value="{{ old('judul', $tugas->judul) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('judul')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="deskripsi" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <textarea id="deskripsi" name="deskripsi" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('deskripsi', $tugas->deskripsi) }}</textarea>
                @error('deskripsi')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700">Deadline</label>
                <input id="deadline" name="deadline" type="date" value="{{ old('deadline', $tugas->deadline) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('deadline')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="prioritas" class="block text-sm font-medium text-gray-700">Prioritas</label>
                <select id="prioritas" name="prioritas" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (['Tinggi', 'Sedang', 'Rendah'] as $prioritas)<option value="{{ $prioritas }}" @selected(old('prioritas', $tugas->prioritas) === $prioritas)>{{ $prioritas }}</option>@endforeach
                </select>
                @error('prioritas')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700">Lampiran tugas (opsional)</label>
                @if ($tugas->file_path)
                    <p class="mb-2 text-sm text-gray-600">File saat ini: <a class="font-medium text-indigo-600 underline" href="{{ route('tugas.download', $tugas) }}">{{ $tugas->file_name }}</a></p>
                    <label class="mb-2 flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remove_file" value="1" class="rounded border-gray-300 text-indigo-600"> Hapus lampiran saat ini</label>
                @endif
                <input id="file" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx" class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
                <p class="mt-1 text-xs text-gray-500">PDF, Word, atau PowerPoint. Maksimal 20 MB.</p>
                @error('file')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3"><a href="{{ route('tugas.index') }}" class="py-2 text-sm text-gray-600">Batal</a><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Perbarui</button></div>
        </form>
    </div></div>
</x-app-layout>
