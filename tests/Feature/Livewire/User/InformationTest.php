<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Livewire\User\Profile\Information;

beforeEach(function () {
    Storage::fake('public');

    $this->user = User::factory()->create();

    $this->actingAs($this->user);
});

it('renders successfully', function () {
    Livewire::test(Information::class)
        ->assertOk()
        ->assertViewIs('livewire.user.profile.information');
});

it('mounts with authenticated user data', function () {
    Livewire::test(Information::class)
        ->assertSet('user.id', $this->user->id)
        ->assertSet('user.name', $this->user->name);
});

it('validates required name', function () {
    Livewire::test(Information::class)
        ->set('user.name', '')
        ->call('save')
        ->assertHasErrors(['user.name' => 'required']);
});

it('validates maximum length of name', function () {
    Livewire::test(Information::class)
        ->set('user.name', str_repeat('a', 256))
        ->call('save')
        ->assertHasErrors(['user.name' => 'max']);
});

it('updates the name without changing the email', function () {
    $email = $this->user->email;

    Livewire::test(Information::class)
        ->set('user.name', 'Updated Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('updated');

    expect($this->user->refresh())
        ->name->toBe('Updated Name')
        ->email->toBe($email);
});

it('dispatches success alert after saving', function () {
    Livewire::test(Information::class)
        ->set('user.name', 'Updated Again')
        ->call('save')
        ->assertDispatched('updated')
        ->assertDispatched('alert:dialog', function (string $event, array $params) {
            return $event === 'alert:dialog' &&
                $params['type'] === 'success' &&
                $params['title'] === 'Done!';
        });
});

it('validates photo is an image', function () {
    $file = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

    Livewire::test(Information::class)
        ->set('photo', $file)
        ->call('save')
        ->assertHasErrors(['photo' => 'image']);
});

it('validates photo size cannot exceed 2mb', function () {
    $file = UploadedFile::fake()->image('avatar.jpg')->size(2049);

    Livewire::test(Information::class)
        ->set('photo', $file)
        ->call('save')
        ->assertHasErrors(['photo' => 'max']);
});

it('uploads and updates profile photo in database and storage', function () {
    $file = UploadedFile::fake()->image('avatar.png');

    Livewire::test(Information::class)
        ->set('photo', $file)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('updated');

    $this->user->refresh();

    expect($this->user->pic_dir)->not->toBeNull();
    Storage::disk('public')->assertExists($this->user->pic_dir);
});

it('replaces old profile photo and deletes previous one from storage', function () {
    $oldFile = UploadedFile::fake()->image('old-avatar.png');
    $oldPath = $oldFile->store('profiles', 'public');
    $this->user->update(['pic_dir' => $oldPath]);

    $newFile = UploadedFile::fake()->image('new-avatar.png');

    Livewire::test(Information::class)
        ->set('photo', $newFile)
        ->call('save')
        ->assertHasNoErrors();

    $this->user->refresh();

    expect($this->user->pic_dir)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($this->user->pic_dir);
});

it('removes profile photo from database and storage', function () {
    $file = UploadedFile::fake()->image('avatar.png');
    $path = $file->store('profiles', 'public');
    $this->user->update(['pic_dir' => $path]);

    Livewire::test(Information::class)
        ->call('removePhoto')
        ->assertHasNoErrors()
        ->assertDispatched('updated');

    $this->user->refresh();

    expect($this->user->pic_dir)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
