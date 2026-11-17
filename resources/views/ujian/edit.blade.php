<x-app-layout>
    <x-slot name="header"><h2 class="text-xl font-semibold text-gray-800 dark:text-white">Edit Agenda Ujian</h2></x-slot>
    <div class="py-10"><div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <form action="{{ route('ujian.update', $agendaUjian) }}" method="POST" class="space-y-6 rounded-2xl bg-white p-6 shadow-sm dark:bg-gray-800 sm:p-8">
            @csrf @method('PUT')
            @include('ujian._form')
            <div class="flex justify-end gap-3"><a href="{{ route('ujian.index') }}" class="py-2 text-sm text-gray-600">Batal</a><button class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700" type="submit">Simpan Perubahan</button></div>
        </form>
    </div></div>
</x-app-layout>
