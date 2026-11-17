<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-3xl font-extrabold tracking-tight text-slate-950">Buat akun baru</h2>
    </div>
    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nama" />
            <x-text-input id="name" class="mt-2 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Nama" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="Email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" placeholder="Masukkan kata sandi" />
            <p class="mt-1.5 text-xs text-slate-500">Minimal 8 karakter.</p>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Ulangi kata sandi" />
            <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi kata sandi" />
            <p class="mt-1.5 text-xs text-slate-500">Ketik ulang kata sandi yang sama seperti di atas.</p>
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <x-primary-button class="w-full justify-center rounded-xl py-3 text-sm font-bold shadow-lg shadow-sky-900/10 transition hover:-translate-y-0.5">Buat akun</x-primary-button>
    </form>
    <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-center">
        <p class="text-sm text-slate-600">Sudah memiliki akun?</p>
        <a href="{{ route('login') }}" class="mt-1 inline-block text-sm font-bold text-sky-700 underline decoration-sky-200 underline-offset-4 hover:text-sky-900">Masuk di sini</a>
    </div>
</x-guest-layout>
