<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#f1f5f9">
        <title>{{ config('app.name', 'RuangKuliah') }}</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-900">
        <main class="relative flex min-h-screen items-center justify-center overflow-hidden bg-gradient-to-br from-slate-100 via-sky-50 to-indigo-100 p-4 sm:p-6 lg:p-8">
            <div class="pointer-events-none absolute -left-28 -top-32 size-96 rounded-full bg-sky-200/50 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-40 -right-24 size-[28rem] rounded-full bg-indigo-200/50 blur-3xl" aria-hidden="true"></div>
            <div class="relative grid w-full max-w-6xl overflow-hidden rounded-[2rem] border border-white/80 bg-white shadow-2xl shadow-slate-900/10 lg:min-h-[640px] lg:grid-cols-[1fr_1fr]">
                <aside class="relative hidden flex-col justify-between overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-sky-900 p-10 text-white lg:flex xl:p-14">
                    <div class="pointer-events-none absolute -right-28 -top-24 size-80 rounded-full border-[40px] border-white/[0.04]" aria-hidden="true"></div>
                    <div class="pointer-events-none absolute -bottom-36 -left-24 size-80 rounded-full bg-sky-400/10 blur-2xl" aria-hidden="true"></div>
                    <a href="/" class="relative inline-flex w-fit items-center gap-3 rounded-xl focus:outline-none focus:ring-4 focus:ring-sky-200" aria-label="RuangKuliah">
                        <x-logo-mark class="size-12 rounded-2xl" />
                        <span>
                            <span class="block text-lg font-extrabold tracking-tight">RuangKuliah</span>
                            <span class="block text-xs font-medium text-sky-200">Teman belajar sehari-hari</span>
                        </span>
                    </a>

                    <div class="relative my-8 max-w-md">
                        <p class="inline-flex items-center gap-2 rounded-full border border-sky-300/20 bg-sky-300/10 px-3 py-1.5 text-xs font-bold uppercase tracking-[0.14em] text-sky-200">
                            <span class="size-1.5 rounded-full bg-sky-300"></span>
                            Kuliah lebih teratur
                        </p>
                        <h1 class="mt-6 text-4xl font-black leading-[1.12] tracking-tight xl:text-5xl">Semua urusan kuliah, lebih mudah.</h1>
                        <p class="mt-5 max-w-sm text-base leading-7 text-slate-300">Simpan jadwal, pantau tugas, dan temukan materi kelas tanpa berpindah-pindah.</p>

                        <div class="mt-7 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-3 backdrop-blur-sm">
                                <span class="grid size-9 place-items-center rounded-xl bg-sky-300/15 text-sky-200" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none"><path d="M7 3v3m10-3v3M4 9h16M5.5 5h13A1.5 1.5 0 0 1 20 6.5v12a1.5 1.5 0 0 1-1.5 1.5h-13A1.5 1.5 0 0 1 4 18.5v-12A1.5 1.5 0 0 1 5.5 5Z" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                                </span>
                                <p class="mt-3 text-sm font-bold">Jadwal</p>
                                <p class="mt-1 text-xs leading-5 text-slate-300">Rencana kelas tersusun.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-3 backdrop-blur-sm">
                                <span class="grid size-9 place-items-center rounded-xl bg-violet-300/15 text-violet-200" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none"><path d="M8 6h12M8 12h12M8 18h12M4 6h.01M4 12h.01M4 18h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round" /></svg>
                                </span>
                                <p class="mt-3 text-sm font-bold">Tugas</p>
                                <p class="mt-1 text-xs leading-5 text-slate-300">Deadline mudah dipantau.</p>
                            </div>
                            <div class="rounded-2xl border border-white/10 bg-white/[0.06] p-3 backdrop-blur-sm">
                                <span class="grid size-9 place-items-center rounded-xl bg-emerald-300/15 text-emerald-200" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" class="size-5" fill="none"><path d="M5 4.75A1.75 1.75 0 0 1 6.75 3H20v16H6.75A2.75 2.75 0 0 0 4 21.75v-17Z" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" /><path d="M4 18.5A2.5 2.5 0 0 1 6.5 16H20M8 7h8m-8 3.5h8" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" /></svg>
                                </span>
                                <p class="mt-3 text-sm font-bold">Materi</p>
                                <p class="mt-1 text-xs leading-5 text-slate-300">Bahan belajar tersimpan.</p>
                            </div>
                        </div>
                    </div>

                    <p class="relative text-xs font-medium text-slate-400">Satu ruang untuk mendukung perjalanan kuliahmu.</p>
                </aside>

                <section class="flex min-h-[calc(100vh-2rem)] items-center bg-white px-6 py-8 sm:px-10 lg:min-h-[640px] lg:px-12 lg:py-4 xl:px-16">
                    <div class="mx-auto w-full max-w-md">
                        <a href="/" class="mb-8 inline-flex items-center gap-2.5 text-sm font-extrabold tracking-tight text-slate-900 lg:hidden" aria-label="RuangKuliah">
                            <x-logo-mark class="size-10 rounded-xl" />
                            RuangKuliah
                        </a>
                        {{ $slot }}
                    </div>
                </section>
            </div>
        </main>
    </body>
</html>
