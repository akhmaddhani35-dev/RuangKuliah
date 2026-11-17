<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('allows a student to update their own profile and password', function () {
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)
        ->patchJson('/api/v1/profile', [
            'name' => 'Nama Baru',
            'email' => 'nama.baru@example.test',
            'role' => 'admin',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Nama Baru')
        ->assertJsonPath('data.email', 'nama.baru@example.test')
        ->assertJsonMissingPath('data.password');

    expect($student->fresh()->role)->toBe('user');
    expect($student->fresh()->email_verified_at)->toBeNull();

    $this->patchJson('/api/v1/profile/password', [
        'current_password' => 'password',
        'password' => 'NewPassword123!',
        'password_confirmation' => 'NewPassword123!',
    ])->assertNoContent();

    expect(Hash::check('NewPassword123!', $student->fresh()->password))->toBeTrue();
});

it('requires the current password to change a student password', function () {
    $student = User::factory()->create(['role' => 'user']);

    $this->actingAs($student)
        ->patchJson('/api/v1/profile/password', [
            'current_password' => 'wrong-password',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('current_password');

    expect(Hash::check('password', $student->fresh()->password))->toBeTrue();
});

it('allows only admins to list and update student accounts', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'user']);
    $anotherAdmin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($student)
        ->getJson('/api/v1/students')
        ->assertForbidden();

    $this->actingAs($student)
        ->patchJson("/api/v1/students/{$student->id}", [
            'name' => 'Tidak Diizinkan',
            'email' => 'blocked@example.test',
        ])
        ->assertForbidden();

    $this->actingAs($admin)
        ->getJson('/api/v1/students')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $student->id)
        ->assertJsonMissingPath('data.0.password');

    $this->patchJson("/api/v1/students/{$student->id}", [
        'name' => 'Akun Diperbarui',
        'email' => 'akun.diperbarui@example.test',
        'password' => 'TemporaryPassword123!',
        'password_confirmation' => 'TemporaryPassword123!',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Akun Diperbarui')
        ->assertJsonPath('data.email', 'akun.diperbarui@example.test');

    expect($student->fresh()->role)->toBe('user');
    expect(Hash::check('TemporaryPassword123!', $student->fresh()->password))->toBeTrue();

    $this->patchJson("/api/v1/students/{$anotherAdmin->id}", [
        'name' => 'Admin Diubah',
        'email' => 'another-admin@example.test',
    ])->assertNotFound();

    expect($anotherAdmin->fresh()->email)->not->toBe('another-admin@example.test');
});
