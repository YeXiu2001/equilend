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
