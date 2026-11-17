<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Daftar Tugas</h2></x-slot>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success')) <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ session('success') }}</div> @endif
            <div class="flex justify-end"><a href="{{ route('tugas.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">+ Tambah Tugas</a></div>
            @php
                $records = $tugas->getCollection();
                $groups = $isAdmin
                    ? [['title' => null, 'items' => $records]]
                    : [
                        ['title' => 'Dari Komting', 'items' => $records->reject(fn ($item) => (int) $item->user_id === (int) auth()->id())],
                        ['title' => 'Pribadi', 'items' => $records->filter(fn ($item) => (int) $item->user_id === (int) auth()->id())],
                    ];
            @endphp
            <div class="space-y-6">
                @foreach ($groups as $group)
                    @if ($group['items']->isNotEmpty() || !$isAdmin)
                        <section class="space-y-3">
                            @if ($group['title'])
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $group['title'] }}</h3>
                                    <span class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">{{ $group['items']->count() }}</span>
                                </div>
                            @endif
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                                @forelse ($group['items'] as $item)
                    @php
                        $prioritasClass = ['Tinggi' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/50 dark:text-rose-200', 'Sedang' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-200', 'Rendah' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'][$item->prioritas];
                        if (!$isAdmin) {
                            $status = $item->my_status ?? 'Belum dikerjakan';
                            $statusClass = ['Belum dikerjakan' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-200', 'Sudah dikerjakan' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-200', 'Selesai' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/50 dark:text-emerald-200'][$status];
                        }
                    @endphp
                    <article class="flex flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-start justify-between gap-3">
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $prioritasClass }}">{{ $item->prioritas }} prioritas</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Deadline {{ \Illuminate\Support\Carbon::parse($item->deadline)->format('d M Y') }}</span>
                        </div>
                        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ $item->course_name ?? $item->nama_mata_kuliah }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $item->judul }}</h3>
                        <p class="mt-2 line-clamp-3 text-sm text-gray-600 dark:text-gray-300">{{ $item->deskripsi }}</p>
                        @if ($item->file_path)
                            <a class="mt-3 inline-flex w-fit items-center gap-2 text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('tugas.download', $item) }}">Unduh: {{ $item->file_name }}</a>
                        @endif
                        <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                            @unless ($isAdmin)
                                <form action="{{ route('tugas.status', $item) }}" method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                    @csrf @method('PATCH')
                                    <label for="status-{{ $item->id }}" class="text-xs font-medium text-gray-500 dark:text-gray-400">Status pribadi</label>
                                    <select id="status-{{ $item->id }}" name="status" onchange="this.form.submit()" class="min-w-0 flex-1 rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                        @foreach (['Belum dikerjakan', 'Sudah dikerjakan', 'Selesai'] as $option)
                                            <option value="{{ $option }}" @selected($status === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            @endunless
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                @unless ($isAdmin)
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">{{ $status }}</span>
                                @endunless
                                @if ($isAdmin || $item->user_id === auth()->id())
                                    <div class="flex items-center gap-3 text-sm font-medium">
                                        <a class="text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('tugas.edit', $item) }}">Edit</a>
                                        <form action="{{ route('tugas.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus tugas ini?')">
                                            @csrf @method('DELETE')
                                            <button class="text-rose-600 hover:underline dark:text-rose-300" type="submit">Hapus</button>
                                        </form>
                                    </div>
                                @else
                                    <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800 dark:bg-violet-900/50 dark:text-violet-200">Dari Komting</span>
                                @endif
                            </div>
                        </div>
                    </article>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 md:col-span-2 xl:col-span-3">Belum ada tugas di bagian ini.</div>
                                @endforelse
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
            <div>{{ $tugas->links() }}</div>
        </div>
    </div>
</x-app-layout>
