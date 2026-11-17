<div>
    <label for="id_mata_kuliah" class="block text-sm font-medium text-gray-700">Mata Kuliah</label>
    <select id="id_mata_kuliah" name="id_mata_kuliah" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Pilih mata kuliah</option>
        @foreach ($mataKuliahs as $mataKuliah)
            <option value="{{ $mataKuliah->id }}" @selected(old('id_mata_kuliah', $agendaUjian?->id_mata_kuliah) == $mataKuliah->id)>{{ $mataKuliah->nama }}</option>
        @endforeach
    </select>
    @error('id_mata_kuliah')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label for="jenis" class="block text-sm font-medium text-gray-700">Jenis Ujian</label>
    <select id="jenis" name="jenis" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @foreach (['UTS', 'UAS'] as $jenis)
            <option value="{{ $jenis }}" @selected(old('jenis', $agendaUjian?->jenis) === $jenis)>{{ $jenis }}</option>
        @endforeach
    </select>
    @error('jenis')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
    <div>
        <label for="tanggal" class="block text-sm font-medium text-gray-700">Tanggal</label>
        <input id="tanggal" name="tanggal" type="date" value="{{ old('tanggal', $agendaUjian?->tanggal?->format('Y-m-d')) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('tanggal')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="waktu_mulai" class="block text-sm font-medium text-gray-700">Mulai</label>
        <input id="waktu_mulai" name="waktu_mulai" type="time" value="{{ old('waktu_mulai', $agendaUjian?->waktu_mulai ? substr($agendaUjian->waktu_mulai, 0, 5) : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('waktu_mulai')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="waktu_selesai" class="block text-sm font-medium text-gray-700">Selesai</label>
        <input id="waktu_selesai" name="waktu_selesai" type="time" value="{{ old('waktu_selesai', $agendaUjian?->waktu_selesai ? substr($agendaUjian->waktu_selesai, 0, 5) : '') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('waktu_selesai')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
    <div>
        <label for="mode" class="block text-sm font-medium text-gray-700">Mode</label>
        <select id="mode" name="mode" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            @foreach (['Offline', 'Online'] as $mode)
                <option value="{{ $mode }}" @selected(old('mode', $agendaUjian?->mode ?? 'Offline') === $mode)>{{ $mode }}</option>
            @endforeach
        </select>
        @error('mode')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="ruang" class="block text-sm font-medium text-gray-700">Ruang (opsional)</label>
        <input id="ruang" name="ruang" type="text" value="{{ old('ruang', $agendaUjian?->ruang) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        @error('ruang')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<div>
    <label for="link_zoom" class="block text-sm font-medium text-gray-700">Link Zoom (wajib untuk ujian online)</label>
    <input id="link_zoom" name="link_zoom" type="url" value="{{ old('link_zoom', $agendaUjian?->link_zoom) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    @error('link_zoom')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
<div>
    <label for="catatan" class="block text-sm font-medium text-gray-700">Catatan (opsional)</label>
    <textarea id="catatan" name="catatan" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('catatan', $agendaUjian?->catatan) }}</textarea>
    @error('catatan')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
</div>
