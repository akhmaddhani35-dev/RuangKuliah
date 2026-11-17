<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Jadwal Mingguan</h2>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Jadwal rutin berulang setiap minggu.</p>
            </div>
            @if ($isAdmin)
                <a href="{{ route('jadwal.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">+ Tambah Jadwal</a>
            @endif
        </div>
    </x-slot>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success')) <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ session('success') }}</div> @endif
            <div class="rounded-xl border border-sky-100 bg-sky-50 p-4 text-sm text-sky-900 dark:border-sky-900 dark:bg-sky-950/40 dark:text-sky-100">
                Jadwal di bawah adalah jadwal rutin. Konfirmasi kelas untuk hari tertentu, seperti libur atau pindah ke Zoom, tetap diumumkan melalui kanal kelas.
            </div>

            @if ($jadwals->isEmpty())
                <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400">Belum ada jadwal mingguan.</div>
            @else
                <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
                    @foreach (['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $hari)
                        @php($jadwalHari = $jadwals->where('hari', $hari)->sortBy('waktu_mulai'))
                        @if ($jadwalHari->isNotEmpty())
                            <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $hari }}</h3>
                                <div class="mt-4 space-y-3">
                                    @foreach ($jadwalHari as $jadwal)
                                        <article class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                                            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                                <div class="min-w-0">
                                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $jadwal->course_name ?? $jadwal->nama_mata_kuliah }}</p>
                                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $jadwal->dosen }} &middot; {{ $jadwal->ruang }}</p>
                                                    <p class="mt-2 text-sm font-semibold text-indigo-700 dark:text-indigo-300">{{ substr($jadwal->waktu_mulai, 0, 5) }} - {{ substr($jadwal->waktu_selesai, 0, 5) }}</p>
                                                </div>
                                                <span class="w-fit rounded-full px-2.5 py-1 text-xs font-semibold {{ $jadwal->mode === 'Online' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200' }}">{{ $jadwal->mode }}</span>
                                            </div>
                                            @if ($jadwal->link_zoom)
                                                <a href="{{ $jadwal->link_zoom }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block break-all text-sm font-medium text-indigo-600 underline dark:text-indigo-300">Buka link Zoom</a>
                                            @endif
                                            @if ($isAdmin)
                                                <div class="mt-4 flex gap-4 border-t border-gray-200 pt-3 text-sm font-medium dark:border-gray-600">
                                                    <a class="text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('jadwal.edit', $jadwal) }}">Edit jadwal rutin</a>
                                                    <form action="{{ route('jadwal.destroy', $jadwal) }}" method="POST" onsubmit="return confirm('Hapus jadwal rutin ini?')">
                                                        @csrf @method('DELETE')
                                                        <button class="text-rose-600 hover:underline dark:text-rose-300" type="submit">Hapus</button>
                                                    </form>
                                                </div>
                                            @endif
                                        </article>
                                    @endforeach
                                </div>
                            </section>
                        @endif
                    @endforeach
                </div>
            @endif

            <section id="agenda-ujian" class="scroll-mt-24 space-y-5 pt-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Jadwal UTS/UAS</h2>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Agenda ujian bertanggal khusus, terpisah dari jadwal rutin mingguan.</p>
                    </div>
                    @if ($isAdmin)
                        <a href="{{ route('ujian.create') }}" class="rounded-lg bg-violet-600 px-4 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-violet-700">+ Tambah Jadwal UTS/UAS</a>
                    @endif
                </div>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($agendaUjians as $agenda)
                        <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                            <div class="flex items-start justify-between gap-3">
                                <span class="rounded-full px-3 py-1 text-xs font-bold {{ $agenda->jenis === 'UTS' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200' }}">{{ $agenda->jenis }}</span>
                                <span class="text-right text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $agenda->tanggal->format('d M Y') }}</span>
                            </div>
                            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">{{ $agenda->course_name }}</h3>
                            <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ substr($agenda->waktu_mulai, 0, 5) }} - {{ substr($agenda->waktu_selesai, 0, 5) }} &middot; {{ $agenda->mode }}</p>
                            @if ($agenda->ruang)<p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $agenda->ruang }}</p>@endif
                            @if ($agenda->link_zoom)<a href="{{ $agenda->link_zoom }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-block break-all text-sm font-medium text-indigo-600 underline dark:text-indigo-300">Buka link ujian online</a>@endif
                            @if ($agenda->catatan)<p class="mt-3 whitespace-pre-line text-sm text-gray-600 dark:text-gray-300">{{ $agenda->catatan }}</p>@endif
                            @if ($isAdmin)
                                <div class="mt-4 flex gap-4 border-t border-gray-100 pt-4 text-sm font-medium dark:border-gray-700">
                                    <a href="{{ route('ujian.edit', $agenda) }}" class="text-indigo-600 hover:underline dark:text-indigo-300">Edit</a>
                                    <form action="{{ route('ujian.destroy', $agenda) }}" method="POST" onsubmit="return confirm('Hapus agenda ujian ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline dark:text-rose-300" type="submit">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-8 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 md:col-span-2 xl:col-span-3">Belum ada jadwal UTS/UAS.</div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
