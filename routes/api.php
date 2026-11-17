<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\AgendaUjianController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\JadwalController;
use App\Http\Controllers\Api\V1\MataKuliahController;
use App\Http\Controllers\Api\V1\MateriController;
use App\Http\Controllers\Api\V1\TugasController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/user', function (Request $request) {
        return response()->json([
            'data' => $request->user()->only(['id', 'name', 'email', 'role', 'program_studi']),
        ]);
    });

    Route::prefix('v1')->name('api.v1.')->group(function () {
        Route::patch('profile', [AccountController::class, 'updateProfile'])->name('profile.update');
        Route::patch('profile/password', [AccountController::class, 'updatePassword'])->name('profile.password');
        Route::get('students', [AccountController::class, 'students'])->name('students.index');
        Route::patch('students/{id}', [AccountController::class, 'updateStudent'])->name('students.update');
        Route::get('dashboard', DashboardController::class)->name('dashboard');
        Route::apiResource('mata-kuliahs', MataKuliahController::class);
        Route::apiResource('jadwals', JadwalController::class);
        Route::apiResource('agenda-ujians', AgendaUjianController::class);
        Route::get('materis/{id}/download', [MateriController::class, 'download'])->name('materis.download');
        Route::apiResource('materis', MateriController::class);
        Route::get('tugas/{id}/download', [TugasController::class, 'download'])->name('tugas.download');
        Route::patch('tugas/{id}/status', [TugasController::class, 'updateStatus'])->name('tugas.status');
        Route::apiResource('tugas', TugasController::class);
    });
});
