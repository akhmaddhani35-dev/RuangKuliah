<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-bold uppercase tracking-[0.12em] text-sky-700">Verifikasi keamanan</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Konfirmasi kata sandi</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Untuk melanjutkan ke bagian aman, masukkan kata sandi akunmu.</p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div>
            <x-input-label for="password" value="Kata sandi" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan kata sandi" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">Konfirmasi dan lanjutkan</x-primary-button>
        </div>
    </form>
</x-guest-layout>
