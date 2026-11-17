<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $this->get('/')
        ->assertRedirect(route('login'));

    $this->get('/login')
        ->assertOk()
        ->assertSee('Selamat datang')
        ->assertSee('Masuk')
        ->assertDontSee('Portal Akademik');
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.',
    ]);
});

test('users see a clear message when their email is not registered', function () {
    $response = $this->post('/login', [
        'email' => 'unknown@example.com',
        'password' => 'some-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'Email atau kata sandi salah. Periksa kembali lalu coba lagi.',
    ]);
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
