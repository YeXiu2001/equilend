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

it('supports infinite scrolling with initial limit of 10', function () {
    Auth::login($this->superAdmin);

    Organization::factory()->count(15)->create();

    $component = Livewire::test(Index::class)
        ->assertSet('limit', 10);

    expect($component->get('rows')->count())->toBe(10)
        ->and($component->get('hasMore'))->toBeTrue();

    $component->call('loadMore')
        ->assertSet('limit', 20);

    expect($component->get('rows')->count())->toBeGreaterThanOrEqual(15)
        ->and($component->get('hasMore'))->toBeFalse();
});

it('filters organizations by search and resets limit to 10', function () {
    Auth::login($this->superAdmin);

    Organization::factory()->create(['name' => 'Unique Searchable Branch', 'code' => 'USB']);
    Organization::factory()->count(12)->create();

    $component = Livewire::test(Index::class)
        ->call('loadMore')
        ->assertSet('limit', 20)
        ->set('search', 'Unique Searchable')
        ->assertSet('limit', 10);

    expect($component->get('rows')->count())->toBe(1)
        ->and($component->get('rows')->first()->name)->toBe('Unique Searchable Branch');
});

it('supports sorting columns', function () {
    Auth::login($this->superAdmin);

    Livewire::test(Index::class)
        ->call('sortBy', 'name')
        ->assertSet('sort.column', 'name')
        ->assertSet('sort.direction', 'asc')
        ->call('sortBy', 'name')
        ->assertSet('sort.direction', 'desc');
});

it('allows superadmin to search and assign user via typeahead', function () {
    Auth::login($this->superAdmin);

    $userToAssign = User::factory()->create(['name' => 'Maria Santos', 'email' => 'maria@example.com']);
    User::factory()->count(15)->create(); // Create extra users to verify limit of 10

    $component = Livewire::test(Create::class)
        ->dispatch('open::create-organization')
        ->assertSet('modal', true);

    // Available users query limited to 10
    expect($component->get('availableUsers')->count())->toBe(10);

    // Filter using typeahead debounced search
    $component->set('user_search', 'Maria Santos');
    expect($component->get('availableUsers')->count())->toBe(1)
        ->and($component->get('availableUsers')->first()->id)->toBe($userToAssign->id);

    // Select user
    $component->call('selectUser', $userToAssign->id)
        ->assertSet('assigned_user_id', $userToAssign->id)
        ->assertSet('user_search', '');

    expect($component->get('selectedUser')->id)->toBe($userToAssign->id);

    // Save organization
    $component->set('name', 'Bacolod Branch')
        ->set('code', 'BCD')
        ->set('email', 'bacolod@equilend.test')
        ->set('phone', '09171234567')
        ->call('save')
        ->assertDispatched('created')
        ->assertSet('modal', false);

    $org = Organization::where('code', 'BCD')->first();
    expect($org)->not->toBeNull()
        ->and($org->name)->toBe('Bacolod Branch')
        ->and($org->assignedUser->id)->toBe($userToAssign->id);
});

it('allows superadmin to update an organization and change assigned user', function () {
    Auth::login($this->superAdmin);

    $oldUser = User::factory()->create(['name' => 'Old User']);
    $newUser = User::factory()->create(['name' => 'New User']);

    $org = Organization::factory()->create([
        'name' => 'Old Branch Name',
        'code' => 'OLD',
    ]);
    $org->members()->attach($oldUser->id, ['role' => 'admin']);

    $component = Livewire::test(Update::class)
        ->dispatch('load::organization', organization: $org->id)
        ->assertSet('modal', true)
        ->assertSet('assigned_user_id', $oldUser->id)
        ->call('clearAssignedUser')
        ->assertSet('assigned_user_id', null)
        ->call('selectUser', $newUser->id)
        ->assertSet('assigned_user_id', $newUser->id)
        ->set('name', 'New Branch Name')
        ->set('code', 'NEW')
        ->call('save')
        ->assertDispatched('updated')
        ->assertSet('modal', false);

    expect($org->fresh()->name)->toBe('New Branch Name')
        ->and($org->fresh()->code)->toBe('NEW')
        ->and($org->fresh()->assignedUser->id)->toBe($newUser->id);
});

it('allows superadmin to delete an organization', function () {
    Auth::login($this->superAdmin);

    $org = Organization::factory()->create(['name' => 'Org to Delete']);

    Livewire::test(Delete::class, ['organization' => $org])
        ->call('delete')
        ->assertDispatched('deleted');

    expect(Organization::find($org->id))->toBeNull();
});
