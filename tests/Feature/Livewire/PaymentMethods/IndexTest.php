<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Models\PaymentMethod;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Livewire\PaymentMethods\Index;
use App\Livewire\PaymentMethods\Create;
use App\Livewire\PaymentMethods\Delete;
use App\Livewire\PaymentMethods\Update;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('superadmin');

    $this->regularUser = User::factory()->create();
});

it('forbids non-superadmin from viewing payment methods index', function () {
    Auth::login($this->regularUser);

    Livewire::test(Index::class)
        ->assertForbidden();
});

it('allows superadmin to view payment methods index', function () {
    Auth::login($this->superAdmin);

    $pm = PaymentMethod::factory()->create([
        'name'         => 'Test Bank Gateway',
        'payment_code' => 'test_bank',
    ]);

    Livewire::test(Index::class)
        ->assertOk()
        ->assertSee('Test Bank Gateway')
        ->assertSee('test_bank');
});

it('supports infinite scrolling for payment methods with initial limit of 10', function () {
    Auth::login($this->superAdmin);

    PaymentMethod::factory()->count(15)->create();

    $component = Livewire::test(Index::class)
        ->assertSet('limit', 10);

    expect($component->get('rows')->count())->toBe(10)
        ->and($component->get('hasMore'))->toBeTrue();

    $component->call('loadMore')
        ->assertSet('limit', 20);

    expect($component->get('rows')->count())->toBeGreaterThanOrEqual(15);
});

it('filters payment methods by search and resets limit to 10', function () {
    Auth::login($this->superAdmin);

    PaymentMethod::factory()->create(['name' => 'BDO Network Bank', 'payment_code' => 'bdo_net']);
    PaymentMethod::factory()->count(12)->create();

    $component = Livewire::test(Index::class)
        ->call('loadMore')
        ->assertSet('limit', 20)
        ->set('search', 'BDO Network')
        ->assertSet('limit', 10);

    expect($component->get('rows')->count())->toBe(1)
        ->and($component->get('rows')->first()->name)->toBe('BDO Network Bank');
});

it('allows superadmin to create a new payment method', function () {
    Auth::login($this->superAdmin);

    Livewire::test(Create::class)
        ->dispatch('open::create-payment-method')
        ->assertSet('modal', true)
        ->set('payment_code', 'seabank')
        ->set('name', 'SeaBank Philippines')
        ->set('description', 'SeaBank Digital Savings')
        ->set('is_active', true)
        ->call('save')
        ->assertDispatched('created')
        ->assertSet('modal', false);

    $pm = PaymentMethod::where('payment_code', 'seabank')->first();
    expect($pm)->not->toBeNull()
        ->and($pm->name)->toBe('SeaBank Philippines');
});

it('allows superadmin to update a payment method', function () {
    Auth::login($this->superAdmin);

    $pm = PaymentMethod::factory()->create([
        'payment_code' => 'bdo_old',
        'name'         => 'BDO Unibank Old',
    ]);

    Livewire::test(Update::class)
        ->dispatch('load::payment-method', paymentMethod: $pm->id)
        ->assertSet('modal', true)
        ->assertSet('name', 'BDO Unibank Old')
        ->set('name', 'BDO Network Bank')
        ->call('save')
        ->assertDispatched('updated')
        ->assertSet('modal', false);

    expect($pm->fresh()->name)->toBe('BDO Network Bank');
});

it('allows superadmin to delete a payment method', function () {
    Auth::login($this->superAdmin);

    $pm = PaymentMethod::factory()->create(['name' => 'PM to Delete']);

    Livewire::test(Delete::class, ['paymentMethod' => $pm])
        ->call('delete')
        ->assertDispatched('deleted');

    expect(PaymentMethod::find($pm->id))->toBeNull();
});
