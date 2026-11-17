<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200)
        ->assertSee('Buat akun baru')
        ->assertSee('Buat akun')
        ->assertSee('placeholder="Nama"', false)
        ->assertSee('placeholder="Email"', false)
        ->assertSee('Minimal 8 karakter.')
        ->assertSee('Ketik ulang kata sandi yang sama seperti di atas.')
        ->assertSee('placeholder="Masukkan kata sandi"', false)
        ->assertSee('placeholder="Ulangi kata sandi"', false)
        ->assertDontSee('Portal Akademik');
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registration requires a password with at least eight characters', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'short-password@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertRedirect('/register')
        ->assertSessionHasErrors([
            'password' => 'Kata sandi minimal 8 karakter.',
        ]);
});

test('registration requires the confirmation to match the password', function () {
    $response = $this->from('/register')->post('/register', [
        'name' => 'Test User',
        'email' => 'mismatched-password@example.com',
        'password' => 'correct-password',
        'password_confirmation' => 'different-password',
    ]);

    $response->assertRedirect('/register')
        ->assertSessionHasErrors([
            'password_confirmation' => 'Kata sandi belum sama. Periksa kembali kolom ulangi kata sandi.',
        ]);
});
