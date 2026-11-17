<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="text-sm font-medium text-indigo-600 dark:text-indigo-300">Ringkasan akademik</p>
                <h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Halo, {{ Auth::user()->name }}</h2>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ([
                    ['label' => 'Mata Kuliah', 'value' => $jumlahMataKuliah, 'tone' => 'indigo'],
                    ['label' => 'Jadwal Hari Ini', 'value' => $jadwalsHariIni->count(), 'tone' => 'sky'],
                    ['label' => 'Deadline 3 Hari', 'value' => $jumlahTugasMendekat, 'tone' => 'amber'],
                    ['label' => $isAdmin ? 'Tugas Dibagikan' : 'Tugas Belum Selesai', 'value' => $isAdmin ? $jumlahTugasDibagikan : $jumlahTugasBelumSelesai, 'tone' => 'rose'],
                ] as $card)
                    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                        <p class="mt-3 text-3xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $card['value'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800 lg:col-span-2">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Jadwal hari ini</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Kegiatan kuliah yang perlu disiapkan.</p>
                        </div>
                        <a href="{{ route('jadwal.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Semua jadwal</a>
                    </div>
                    <div class="mt-5 divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($jadwalsHariIni as $jadwal)
                            <div class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="font-medium text-gray-900 dark:text-white">{{ $jadwal->course_name ?? $jadwal->nama_mata_kuliah }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $jadwal->ruang }} &middot; {{ $jadwal->dosen }}</p>
                                </div>
                                <span class="w-fit rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">
                                    {{ substr($jadwal->waktu_mulai, 0, 5) }} - {{ substr($jadwal->waktu_selesai, 0, 5) }}
                                </span>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Tidak ada jadwal kuliah hari ini. Semoga harimu menyenangkan!</p>
                        @endforelse
                    </div>
                </article>

                @if ($isAdmin)
                    <article class="rounded-2xl border border-indigo-100 bg-indigo-50 p-5 shadow-sm dark:border-indigo-900 dark:bg-indigo-950/40 lg:col-span-1">
                        <h3 class="font-semibold text-gray-900 dark:text-white">Bagikan informasi tugas</h3>
                        <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Tugas yang ditambahkan admin akan dibagikan kepada mahasiswa. Progres pengerjaan dikelola oleh setiap mahasiswa.</p>
                        <a href="{{ route('tugas.create') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Tambah tugas untuk dibagikan</a>
                    </article>
                @else
                    <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="font-semibold text-gray-900 dark:text-white">Progres tugas</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tugas berstatus selesai.</p>
                            </div>
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-300">{{ $persentaseSelesai }}%</span>
                        </div>
                        <div class="mt-5 h-3 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700" role="progressbar" aria-label="Progres tugas selesai" aria-valuenow="{{ $persentaseSelesai }}" aria-valuemin="0" aria-valuemax="100">
                            <div class="h-full rounded-full bg-indigo-600 transition-all" style="width: {{ $persentaseSelesai }}%"></div>
                        </div>
                        <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">{{ $jumlahTugasBelumSelesai }} tugas masih perlu dituntaskan.</p>
                        <a href="{{ route('tugas.index') }}" class="mt-5 inline-flex rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">Lihat daftar tugas</a>
                    </article>
                @endif
            </section>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Deadline mendekat</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tugas dengan tenggat dalam tiga hari.</p>
                        </div>
                        <a href="{{ route('tugas.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Semua tugas</a>
                    </div>
                    <div class="mt-4 space-y-3">
                        @forelse ($tugasMendekat as $tugas)
                            @php
                                $prioritasClass = ['Tinggi' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200', 'Sedang' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200', 'Rendah' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'][$tugas->prioritas];
                                if (!$isAdmin) {
                                    $status = $tugas->my_status ?? 'Belum dikerjakan';
                                }
                            @endphp
                            <div class="flex flex-col gap-3 rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900 dark:text-white">{{ $tugas->judul }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $tugas->course_name ?? $tugas->nama_mata_kuliah }} &middot; {{ \Illuminate\Support\Carbon::parse($tugas->deadline)->format('d M Y') }}</p>
                                </div>
                                <div class="flex flex-wrap gap-2">
                                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $prioritasClass }}">{{ $tugas->prioritas }}</span>
                                    @unless ($isAdmin)
                                        <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-700 dark:bg-gray-600 dark:text-gray-200">{{ $status }}</span>
                                    @endunless
                                </div>
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada deadline dalam tiga hari ke depan.</p>
                        @endforelse
                    </div>
                </article>

                <article class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="font-semibold text-gray-900 dark:text-white">Materi terbaru</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Materi terakhir yang ditambahkan.</p>
                        </div>
                        <a href="{{ route('materi.index') }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Semua materi</a>
                    </div>
                    <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($materiTerbaru as $materi)
                            <a href="{{ route('materi.show', $materi) }}" class="flex items-center justify-between gap-3 py-4">
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-gray-900 dark:text-white">{{ $materi->judul }}</p>
                                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $materi->course_name ?? $materi->nama_mata_kuliah }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">{{ $materi->tipe }}</span>
                            </a>
                        @empty
                            <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">Belum ada materi.</p>
                        @endforelse
                    </div>
                </article>
            </section>

            <section class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="font-semibold text-gray-900 dark:text-white">Agenda UTS/UAS mendatang</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Ujian dalam 30 hari ke depan.</p>
                    </div>
                    <a href="{{ route('jadwal.index') }}#agenda-ujian" class="text-sm font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-300">Semua agenda</a>
                </div>
                <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                    @forelse ($ujianMendatang as $ujian)
                        <article class="rounded-xl bg-gray-50 p-4 dark:bg-gray-700/50">
                            <div class="flex items-center justify-between gap-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $ujian->jenis === 'UTS' ? 'bg-violet-100 text-violet-700 dark:bg-violet-900/50 dark:text-violet-200' : 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200' }}">{{ $ujian->jenis }}</span>
                                <span class="text-sm font-semibold text-gray-600 dark:text-gray-300">{{ $ujian->tanggal->format('d M Y') }}</span>
                            </div>
                            <p class="mt-3 font-medium text-gray-900 dark:text-white">{{ $ujian->course_name }}</p>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ substr($ujian->waktu_mulai, 0, 5) }} - {{ substr($ujian->waktu_selesai, 0, 5) }} &middot; {{ $ujian->mode }}</p>
                        </article>
                    @empty
                        <p class="py-5 text-sm text-gray-500 dark:text-gray-400">Belum ada agenda ujian dalam 30 hari ke depan.</p>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
