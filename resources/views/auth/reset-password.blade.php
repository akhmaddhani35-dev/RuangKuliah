<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-bold uppercase tracking-[0.12em] text-sky-700">Keamanan akun</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Buat kata sandi baru</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Pilih kata sandi yang kuat dan mudah kamu ingat.</p>
    </div>
    <form method="POST" action="{{ route('password.store') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" value="Kata sandi baru" />
            <x-text-input id="password" class="mt-2 block w-full" type="password" name="password" required autocomplete="new-password" placeholder="Minimal 8 karakter" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" value="Ulangi kata sandi baru" />
            <x-text-input id="password_confirmation" class="mt-2 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" placeholder="Ketik ulang kata sandi" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">Simpan kata sandi baru</x-primary-button>
        </div>
    </form>
</x-guest-layout>
