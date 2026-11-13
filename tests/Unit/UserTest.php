<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Storage;

uses(Tests\TestCase::class);

test('pic_dir is fillable', function () {
    $user = new User([
        'name'    => 'John Doe',
        'email'   => 'john@example.com',
        'pic_dir' => 'profiles/avatar.png',
    ]);

    expect($user->pic_dir)->toBe('profiles/avatar.png');
});

test('pic_url returns null when pic_dir is null', function () {
    $user = new User();

    expect($user->pic_url)->toBeNull();
});

test('pic_url returns storage url for relative path', function () {
    $user = new User([
        'pic_dir' => 'profiles/my-avatar.png',
    ]);

    expect($user->pic_url)->toBe(Storage::disk('public')->url('profiles/my-avatar.png'));
});

test('pic_url returns the original string if already a full url', function () {
    $url  = 'https://example.com/images/avatar.jpg';
    $user = new User([
        'pic_dir' => $url,
    ]);

    expect($user->pic_url)->toBe($url);
});
