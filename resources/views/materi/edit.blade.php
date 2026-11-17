<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Edit Materi</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form action="{{ route('materi.update', $materi) }}" method="POST" enctype="multipart/form-data" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800 sm:p-8">
            @csrf @method('PUT')
            <div>
                <label for="id_mata_kuliah" class="block text-sm font-medium text-gray-700">Mata Kuliah (opsional)</label>
                <select id="id_mata_kuliah" name="id_mata_kuliah" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Tanpa mata kuliah</option>
                    @foreach ($mata_kuliahs as $mataKuliah)<option value="{{ $mataKuliah->id }}" data-name="{{ $mataKuliah->nama }}" @selected(old('id_mata_kuliah', $materi->id_mata_kuliah) == $mataKuliah->id)>{{ $mataKuliah->nama }}</option>@endforeach
                </select>
                @error('id_mata_kuliah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="nama_mata_kuliah" class="block text-sm font-medium text-gray-700">Nama Mata Kuliah</label>
                <input id="nama_mata_kuliah" name="nama_mata_kuliah" type="text" value="{{ old('nama_mata_kuliah', $materi->nama_mata_kuliah) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-xs text-gray-500">Wajib diisi jika tidak memilih mata kuliah.</p>
                @error('nama_mata_kuliah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="judul" class="block text-sm font-medium text-gray-700">Judul</label>
                <input id="judul" name="judul" type="text" maxlength="200" value="{{ old('judul', $materi->judul) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('judul')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="pertemuan" class="block text-sm font-medium text-gray-700">Pertemuan</label>
                <input id="pertemuan" name="pertemuan" type="text" value="{{ old('pertemuan', $materi->pertemuan) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('pertemuan')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="tipe" class="block text-sm font-medium text-gray-700">Tipe</label>
                <select id="tipe" name="tipe" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (['File', 'Link'] as $tipe)<option value="{{ $tipe }}" @selected(old('tipe', $materi->tipe) === $tipe)>{{ $tipe }}</option>@endforeach
                </select>
                @error('tipe')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="detail" class="block text-sm font-medium text-gray-700">Detail</label>
                <input id="detail" name="detail" type="text" value="{{ old('detail', $materi->detail) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('detail')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="file" class="block text-sm font-medium text-gray-700">Unggah file materi (opsional)</label>
                @if ($materi->file_path)
                    <p class="mb-2 text-sm text-gray-600">File saat ini: <a class="font-medium text-indigo-600 underline" href="{{ route('materi.download', $materi) }}">{{ $materi->file_name }}</a></p>
                    <label class="mb-2 flex items-center gap-2 text-sm text-gray-600"><input type="checkbox" name="remove_file" value="1" class="rounded border-gray-300 text-indigo-600"> Hapus file saat ini</label>
                @endif
                <input id="file" name="file" type="file" accept=".pdf,.doc,.docx,.ppt,.pptx" class="mt-1 block w-full rounded-md border border-gray-300 p-2 text-sm text-gray-700 file:mr-4 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:font-semibold file:text-indigo-700">
                <p class="mt-1 text-xs text-gray-500">PDF, Word, atau PowerPoint. Maksimal 20 MB.</p>
                @error('file')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                @error('detail')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3"><a href="{{ route('materi.index') }}" class="py-2 text-sm text-gray-600">Batal</a><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Perbarui</button></div>
        </form>
    </div></div>
    <script>
        document.getElementById('id_mata_kuliah').addEventListener('change', function () {
            if (this.value) document.getElementById('nama_mata_kuliah').value = this.options[this.selectedIndex].dataset.name;
        });
    </script>
</x-app-layout>
