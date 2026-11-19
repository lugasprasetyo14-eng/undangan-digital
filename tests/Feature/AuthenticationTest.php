<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a visitor to register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Ayu Rahma',
        'email' => 'ayu@example.test',
        'phone' => '081234567890',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'ayu@example.test',
        'role' => 'user',
        'phone' => '081234567890',
    ]);
});

it('never accepts an admin role from registration input', function () {
    $this->post(route('register.store'), [
        'name' => 'Public User',
        'email' => 'public@example.test',
        'role' => 'admin',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])->assertRedirect('/dashboard');

    expect(User::where('email', 'public@example.test')->value('role'))->toBe('user');
});

it('rejects invalid registration data', function () {
    $response = $this->from(route('register'))->post(route('register.store'), [
        'name' => '',
        'email' => 'not-an-email',
        'password' => 'short',
        'password_confirmation' => 'different',
    ]);

    $response->assertRedirect(route('register'));
    $response->assertSessionHasErrors(['name', 'email', 'password']);
    $this->assertGuest();
});

it('allows a registered user to login and logout', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password123',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);

    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();
});

it('rejects incorrect login credentials', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->from(route('login'))->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
});