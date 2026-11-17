<x-guest-layout>
    <x-auth-session-status :status="session('status')" />

    <div class="mb-7">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950">Selamat datang</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Lanjutkan melihat jadwal, tugas, dan materi kuliah.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Alamat email" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-2">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2.5 rounded-lg text-sm text-slate-600">
                <input id="remember_me" type="checkbox" class="size-4 rounded border-slate-300 text-sky-700 shadow-sm focus:ring-4 focus:ring-sky-100" name="remember" @checked(old('remember'))>
                <span>Ingat saya di perangkat ini</span>
            </label>
            @if (Route::has('password.request'))
                <a class="inline-flex rounded text-xs font-semibold text-sky-700 underline decoration-sky-200 underline-offset-4 transition hover:text-sky-900 focus:outline-none focus:ring-4 focus:ring-sky-100" href="{{ route('password.request') }}">Lupa kata sandi?</a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center rounded-xl py-3 text-sm font-bold shadow-lg shadow-sky-900/10 transition hover:-translate-y-0.5">
            Masuk
        </x-primary-button>
    </form>

    @if (Route::has('register'))
        <div class="mt-7 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">
            <p class="text-sm text-slate-600">Belum memiliki akun?</p>
            <a href="{{ route('register') }}" class="mt-1 inline-block text-sm font-bold text-sky-700 underline decoration-sky-200 underline-offset-4 hover:text-sky-900">Daftar sekarang</a>
        </div>
    @endif
</x-guest-layout>
