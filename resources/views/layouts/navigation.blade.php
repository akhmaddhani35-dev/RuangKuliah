<nav x-data="{ open: false }" class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/95 backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-4">
            <a href="{{ route('spa') }}" class="flex items-center gap-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-sky-100" aria-label="RuangKuliah">
                <x-logo-mark class="size-10 rounded-xl" />
                <span><span class="block text-sm font-extrabold tracking-tight text-slate-900 dark:text-white">RuangKuliah</span><span class="block text-xs text-slate-500 dark:text-slate-400">Manajemen akademik</span></span>
            </a>

            <a href="{{ route('spa') }}" class="hidden min-h-10 items-center rounded-xl bg-sky-50 px-4 text-sm font-bold text-sky-800 transition hover:bg-sky-100 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:bg-sky-950 dark:text-sky-200 dark:hover:bg-sky-900 sm:inline-flex">
                Buka aplikasi
            </a>

            <div class="flex items-center gap-2">
                <button type="button" data-theme-toggle class="hidden min-h-10 rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 sm:inline-flex sm:items-center">
                    <span data-theme-label>Tema gelap</span>
                </button>
                <span class="hidden rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300 md:inline-flex">{{ Auth::user()->role === 'admin' ? 'Komting' : 'Mahasiswa' }}</span>
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex min-h-10 items-center gap-2 rounded-xl border border-transparent px-3 text-sm font-bold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:text-slate-200 dark:hover:bg-slate-800">
                            <span class="grid size-8 place-items-center rounded-full bg-sky-100 text-xs font-extrabold text-sky-800 dark:bg-sky-950 dark:text-sky-200">{{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}</span>
                            <span class="hidden max-w-32 truncate sm:inline">{{ Auth::user()->name }}</span>
                            <svg class="size-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 7.47a.75.75 0 0 1 1.06 0L10 11.19l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 8.53a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-700">
                            <p class="truncate text-sm font-bold text-slate-900 dark:text-white">{{ Auth::user()->name }}</p>
                            <p class="truncate text-xs text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Profil akun</x-dropdown-link>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full px-4 py-2 text-start text-sm leading-5 text-rose-700 transition hover:bg-rose-50 focus:bg-rose-50 focus:outline-none dark:text-rose-300 dark:hover:bg-rose-950 dark:focus:bg-rose-950">Keluar</button>
                        </form>
                    </x-slot>
                </x-dropdown>
                <button type="button" @click="open = !open" :aria-expanded="open.toString()" :aria-label="open ? 'Tutup navigasi' : 'Buka navigasi'" class="grid size-10 place-items-center rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 focus:outline-none focus:ring-4 focus:ring-sky-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800 sm:hidden">
                    <svg x-show="!open" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke-width="1.8" stroke-linecap="round" /></svg>
                    <svg x-show="open" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="m6 6 12 12M18 6 6 18" stroke-width="1.8" stroke-linecap="round" /></svg>
                </button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="open" x-transition class="border-t border-slate-200 bg-white px-4 py-3 dark:border-slate-800 dark:bg-slate-900 sm:hidden">
        <a href="{{ route('spa') }}" class="block min-h-11 rounded-xl px-3 py-3 text-sm font-bold text-sky-800 hover:bg-sky-50 dark:text-sky-200 dark:hover:bg-sky-950">Buka aplikasi akademik</a>
        <a href="{{ route('profile.edit') }}" class="block min-h-11 rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800">Profil akun</a>
        <button type="button" data-theme-toggle class="min-h-11 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-800"><span data-theme-label>Tema gelap</span></button>
        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-slate-100 pt-2 dark:border-slate-800">
            @csrf
            <button type="submit" class="min-h-11 w-full rounded-xl px-3 py-3 text-left text-sm font-semibold text-rose-700 hover:bg-rose-50 dark:text-rose-300 dark:hover:bg-rose-950">Keluar dari akun</button>
        </form>
    </div>
</nav>
