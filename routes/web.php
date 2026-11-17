<?php

use App\Http\Controllers\AgendaUjianController;
use App\Http\Controllers\JadwalController;
use App\Http\Controllers\MataKuliahController;
use App\Http\Controllers\MateriController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TugasController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::redirect('/dashboard', '/app')->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/materi/{id}/download', [MateriController::class, 'download'])->name('materi.download');
    Route::get('/tugas/{id}/download', [TugasController::class, 'download'])->name('tugas.download');
    Route::patch('/tugas/{id}/status', [TugasController::class, 'updateStatus'])->name('tugas.status');
    Route::resource('matakuliah', MataKuliahController::class);
    Route::resource('jadwal', JadwalController::class);
    Route::resource('ujian', AgendaUjianController::class);
    Route::resource('materi', MateriController::class);
    Route::resource('tugas', TugasController::class);
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::view('/app/{path?}', 'spa')
    ->where('path', '.*')
    ->middleware(['auth', 'verified'])
    ->name('spa');
