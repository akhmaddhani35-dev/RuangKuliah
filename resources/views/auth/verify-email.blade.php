<x-guest-layout>
    <div class="mb-6">
        <p class="text-sm font-bold uppercase tracking-[0.12em] text-sky-700">Satu langkah lagi</p>
        <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950">Verifikasi email</h2>
        <p class="mt-2 text-sm leading-6 text-slate-600">Kami sudah mengirim tautan verifikasi ke emailmu. Buka tautan tersebut untuk melanjutkan.</p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div role="status" aria-live="polite" class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">
            Tautan verifikasi baru sudah dikirim. Periksa kotak masuk dan folder spam.
        </div>
    @endif

    <div class="space-y-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button class="w-full">Kirim ulang tautan verifikasi</x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="text-center">
            @csrf
            <button type="submit" class="min-h-10 rounded-lg px-3 text-sm font-semibold text-slate-600 underline underline-offset-4 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-sky-100">
                Keluar dari akun
            </button>
        </form>
    </div>
</x-guest-layout>
