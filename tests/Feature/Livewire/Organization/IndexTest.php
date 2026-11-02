<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Models\Organization;
use Spatie\Permission\Models\Role;
use App\Livewire\Organization\Index;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Organization\Create;
use App\Livewire\Organization\Delete;
use App\Livewire\Organization\Update;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('superadmin');

    $this->regularUser = User::factory()->create();
});

it('forbids non-superadmin users from viewing organization index', function () {
    Auth::login($this->regularUser);

    Livewire::test(Index::class)
        ->assertForbidden();
});

it('allows superadmin to view organization index and lists organizations', function () {
    Auth::login($this->superAdmin);

    $org = Organization::factory()->create([
        'name' => 'Test Super Org',
        'code' => 'TSO',
    ]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Test Super Org')
        ->assertSee('TSO');
});

it('allows superadmin to create an organization and assign an unassigned user', function () {
    Auth::login($this->superAdmin);

    $userToAssign = User::factory()->create(['name' => 'Assigned Manager']);

    Livewire::test(Create::class)
        ->dispatch('open::create-organization')
        ->assertSet('modal', true)
        ->set('name', 'Bacolod Branch')
        ->set('code', 'BCD')
        ->set('assigned_user_id', $userToAssign->id)
        ->call('save')
        ->assertDispatched('created')
        ->assertSet('modal', false);

    $org = Organization::where('code', 'BCD')->first();
    expect($org)->not->toBeNull()
        ->and($org->name)->toBe('Bacolod Branch')
        ->and($org->assignedUser->id)->toBe($userToAssign->id);
});

it('allows superadmin to update an organization', function () {
    Auth::login($this->superAdmin);

    $org = Organization::factory()->create([
        'name' => 'Old Branch Name',
        'code' => 'OLD',
    ]);

    Livewire::test(Update::class)
        ->dispatch('load::organization', organization: $org->id)
        ->assertSet('modal', true)
        ->assertSet('name', 'Old Branch Name')
        ->set('name', 'New Branch Name')
        ->set('code', 'NEW')
        ->call('save')
        ->assertDispatched('updated')
        ->assertSet('modal', false);

    expect($org->fresh()->name)->toBe('New Branch Name')
        ->and($org->fresh()->code)->toBe('NEW');
});

it('allows superadmin to delete an organization', function () {
    Auth::login($this->superAdmin);

    $org = Organization::factory()->create(['name' => 'Org to Delete']);

    Livewire::test(Delete::class, ['organization' => $org])
        ->call('delete')
        ->assertDispatched('deleted');

    expect(Organization::find($org->id))->toBeNull();
});
