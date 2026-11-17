<x-app-layout>
    <x-slot name="header"><h2 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">Materi Kuliah</h2></x-slot>
    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('success')) <div role="status" class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200">{{ session('success') }}</div> @endif
            <div class="flex justify-end"><a href="{{ route('materi.create') }}" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">+ Tambah Materi</a></div>
            @php
                $records = $materis->getCollection();
                $groups = $isAdmin
                    ? [['title' => null, 'items' => $records]]
                    : [
                        ['title' => 'Dari Komting', 'items' => $records->reject(fn ($materi) => (int) $materi->user_id === (int) auth()->id())],
                        ['title' => 'Pribadi', 'items' => $records->filter(fn ($materi) => (int) $materi->user_id === (int) auth()->id())],
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
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                                @forelse ($group['items'] as $materi)
                    <article class="flex flex-col rounded-2xl border border-gray-100 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-gray-700 dark:bg-gray-800">
                        <div class="flex items-center justify-between gap-3">
                            <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700 dark:bg-sky-900/50 dark:text-sky-200">{{ $materi->tipe }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">{{ $materi->pertemuan }}</span>
                        </div>
                        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-indigo-600 dark:text-indigo-300">{{ $materi->course_name ?? $materi->nama_mata_kuliah }}</p>
                        <h3 class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $materi->judul }}</h3>
                        <p class="mt-2 break-words text-sm text-gray-600 dark:text-gray-300">{{ $materi->detail }}</p>
                        <div class="mt-auto flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 pt-4 dark:border-gray-700">
                            @if ($materi->file_path)
                                <a class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('materi.download', $materi) }}">Unduh file</a>
                            @else
                                <a class="text-sm font-medium text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('materi.show', $materi) }}">Detail materi</a>
                            @endif
                            @if ($isAdmin || $materi->user_id === auth()->id())
                                <div class="flex items-center gap-3 text-sm font-medium">
                                    <a class="text-indigo-600 hover:underline dark:text-indigo-300" href="{{ route('materi.edit', $materi) }}">Edit</a>
                                    <form action="{{ route('materi.destroy', $materi) }}" method="POST" onsubmit="return confirm('Hapus materi ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-rose-600 hover:underline dark:text-rose-300" type="submit">Hapus</button>
                                    </form>
                                </div>
                            @else
                                <span class="inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-xs font-semibold text-violet-800 dark:bg-violet-900/50 dark:text-violet-200">Dari Komting</span>
                            @endif
                        </div>
                    </article>
                                @empty
                                    <div class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center text-sm text-gray-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-400 sm:col-span-2 xl:col-span-3">Belum ada materi di bagian ini.</div>
                                @endforelse
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
            <div>{{ $materis->links() }}</div>
        </div>
    </div>
</x-app-layout>
