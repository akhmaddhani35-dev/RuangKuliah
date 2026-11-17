<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800">Tambah Jadwal</h2></x-slot>
    <div class="py-12"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form action="{{ route('jadwal.store') }}" method="POST" class="space-y-6 rounded-lg bg-white p-6 shadow-sm sm:p-8">
            @csrf
            <div>
                <label for="id_mata_kuliah" class="block text-sm font-medium text-gray-700">Mata Kuliah</label>
                <select id="id_mata_kuliah" name="id_mata_kuliah" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih mata kuliah</option>
                    @foreach ($mata_kuliahs as $mataKuliah)<option value="{{ $mataKuliah->id }}" @selected(old('id_mata_kuliah') == $mataKuliah->id)>{{ $mataKuliah->nama }}</option>@endforeach
                </select>
                @error('id_mata_kuliah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="hari" class="block text-sm font-medium text-gray-700">Hari</label>
                <select id="hari" name="hari" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih hari</option>
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)<option value="{{ $hari }}" @selected(old('hari') === $hari)>{{ $hari }}</option>@endforeach
                </select>
                @error('hari')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @foreach (['waktu_mulai' => 'Waktu Mulai', 'waktu_selesai' => 'Waktu Selesai', 'ruang' => 'Ruang', 'dosen' => 'Dosen'] as $field => $label)
                <div>
                    <label for="{{ $field }}" class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                    <input id="{{ $field }}" name="{{ $field }}" type="{{ str_starts_with($field, 'waktu_') ? 'time' : 'text' }}" value="{{ old($field) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error($field)<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
            @endforeach
            <div>
                <label for="mode" class="block text-sm font-medium text-gray-700">Mode</label>
                <select id="mode" name="mode" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Pilih mode</option><option value="Offline" @selected(old('mode') === 'Offline')>Offline</option><option value="Online" @selected(old('mode') === 'Online')>Online</option>
                </select>
                @error('mode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="link_zoom" class="block text-sm font-medium text-gray-700">Link Zoom (opsional)</label>
                <input id="link_zoom" name="link_zoom" type="text" value="{{ old('link_zoom') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('link_zoom')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="flex justify-end gap-3"><a href="{{ route('jadwal.index') }}" class="py-2 text-sm text-gray-600">Batal</a><button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Simpan</button></div>
        </form>
    </div></div>
</x-app-layout>
