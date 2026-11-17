<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-bold uppercase tracking-[0.12em] text-sky-700">Bantuan akun</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Atur ulang kata sandi</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Masukkan email akunmu. Jika terdaftar, kami akan mengirim tautan untuk membuat kata sandi baru.</p>
    </div>

    <x-auth-session-status :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="mt-2 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="email" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">Kirim tautan pemulihan</x-primary-button>
        </div>
    </form>
    <p class="mt-6 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-sky-700 underline decoration-sky-200 underline-offset-4 hover:text-sky-900">Kembali ke halaman masuk</a></p>
</x-guest-layout>
