<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('protects dashboard and profile routes from guests', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
    $this->get(route('profile.edit'))->assertRedirect(route('login'));
});

it('allows a user to update their profile', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->put(route('profile.update'), [
        'name' => 'Nama Baru',
        'email' => 'baru@example.test',
        'phone' => '081111111111',
    ])->assertRedirect(route('profile.edit'));

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Nama Baru',
        'email' => 'baru@example.test',
        'phone' => '081111111111',
    ]);
});

it('never accepts an admin role from profile input', function () {
    $user = User::factory()->create(['role' => 'user']);
    $this->actingAs($user);

    $this->put(route('profile.update'), [
        'name' => 'Nama Tetap User',
        'email' => $user->email,
        'role' => 'admin',
    ])->assertRedirect(route('profile.edit'));

    expect($user->refresh()->role)->toBe('user');
});

it('rejects invalid profile updates', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->from(route('profile.edit'))->put(route('profile.update'), [
        'name' => '',
        'email' => 'invalid',
    ])->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors(['name', 'email']);
});

it('limits admin route to admin users', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin'))
        ->assertForbidden();

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin'))
        ->assertOk();
});