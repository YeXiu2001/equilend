<?php

declare(strict_types=1);

use App\Models\User;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);
});

it('renders the login page', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Log in');
});

it('authenticates a user and redirects to dashboard by default', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'Test123!',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

it('redirects superadmin to dashboard after login', function () {
    $superadmin = User::factory()->create();
    $superadmin->assignRole('superadmin');

    $this->post(route('login.store'), [
        'email'    => $superadmin->email,
        'password' => 'Test123!',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($superadmin);
});

it('redirects organization admin to dashboard after login', function () {
    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');

    $this->post(route('login.store'), [
        'email'    => $admin->email,
        'password' => 'Test123!',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($admin);
});

it('redirects member borrower to borrower portal browse after login', function () {
    $borrower = User::factory()->create();
    $borrower->assignRole('member');

    $this->post(route('login.store'), [
        'email'    => $borrower->email,
        'password' => 'Test123!',
    ])->assertRedirect('/portal/browse');

    $this->assertAuthenticatedAs($borrower);
});

it('does not authenticate with invalid credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email'    => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors();

    $this->assertGuest();
});

it('logs out an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');

    $this->assertGuest();
});
