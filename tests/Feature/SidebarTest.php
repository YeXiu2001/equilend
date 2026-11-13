<?php

declare(strict_types=1);

use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create([
        'pic_dir' => 'profiles/test-avatar.jpg',
    ]);
});

it('renders the profile link in the sidebar navigating to user profile', function () {
    $response = $this->actingAs($this->user)
        ->get(route('starter-page'));

    $response->assertOk();
    $response->assertSee(route('user.profile'), false);
    $response->assertSee('avatar avatar-placeholder mb-2', false);
    $response->assertSee('test-avatar.jpg', false);
});

it('renders circulation desk routes in the sidebar for staff users', function () {
    Spatie\Permission\Models\Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);

    $admin = User::factory()->create();
    $admin->assignRole('organization_admin');

    $response = $this->actingAs($admin)->get(route('circulation.desk'));

    $response->assertOk();
    $response->assertSee(route('circulation.desk'), false);
    $response->assertSee(route('circulation.loans'), false);
    $response->assertSee(route('circulation.reservations'), false);
    $response->assertSee(route('circulation.overdue'), false);
    $response->assertSee(route('circulation.calendar'), false);
});
