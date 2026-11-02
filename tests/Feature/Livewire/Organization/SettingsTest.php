<?php

declare(strict_types=1);

use App\Models\User;
use Livewire\Livewire;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use App\Livewire\Organization\Settings;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);

    $this->superAdmin = User::factory()->create();
    $this->superAdmin->assignRole('superadmin');

    $this->manager = User::factory()->create(['name' => 'Branch Lead']);
    $this->manager->assignRole('organization_admin');

    $this->organization = Organization::factory()->create([
        'name' => 'Davao Lending Hub',
        'code' => 'DVO',
    ]);
    $this->organization->members()->attach($this->manager->id, ['role' => 'admin']);

    $this->gcash = PaymentMethod::firstOrCreate(
        ['payment_code' => 'gcash'],
        ['name' => 'GCash', 'is_active' => true]
    );

    $this->maya = PaymentMethod::firstOrCreate(
        ['payment_code' => 'maya'],
        ['name' => 'Maya', 'is_active' => true]
    );
});

it('shows empty state when authenticated user has no assigned organization', function () {
    $unassignedUser = User::factory()->create();
    Auth::login($unassignedUser);

    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSee('No Organization Assigned');
});

it('loads assigned organization data and allows updating profile details', function () {
    Auth::login($this->manager);

    Livewire::test(Settings::class)
        ->assertOk()
        ->assertSet('name', 'Davao Lending Hub')
        ->assertSet('code', 'DVO')
        ->set('name', 'Davao Prime Lending Hub')
        ->set('phone', '09170001111')
        ->call('save')
        ->assertDispatched('alert:dialog');

    expect($this->organization->fresh()->name)->toBe('Davao Prime Lending Hub')
        ->and($this->organization->fresh()->phone)->toBe('09170001111');
});

it('allows configuring payment methods with multiple account numbers and single account name', function () {
    Auth::login($this->manager);

    Livewire::test(Settings::class)
        ->set("paymentConfigs.{$this->gcash->id}.is_enabled", true)
        ->set("paymentConfigs.{$this->gcash->id}.account_name", 'Davao Hub Corp')
        ->set("paymentConfigs.{$this->gcash->id}.account_numbers.0", '09171112222')
        ->call('addAccountNumber', $this->gcash->id)
        ->set("paymentConfigs.{$this->gcash->id}.account_numbers.1", '09173334444')
        ->call('save');

    $config = $this->organization->fresh()->organizationPaymentMethods()
        ->where('payment_method_id', $this->gcash->id)
        ->first();

    expect($config)->not->toBeNull()
        ->and($config->is_enabled)->toBeTrue()
        ->and($config->account_name)->toBe('Davao Hub Corp')
        ->and($config->account_numbers)->toEqual(['09171112222', '09173334444']);
});

it('allows disabling a payment method', function () {
    Auth::login($this->manager);

    $this->organization->organizationPaymentMethods()->create([
        'payment_method_id' => $this->gcash->id,
        'account_name'      => 'Davao Hub Corp',
        'account_numbers'   => ['09171112222'],
        'is_enabled'        => true,
    ]);

    Livewire::test(Settings::class)
        ->assertSet("paymentConfigs.{$this->gcash->id}.is_enabled", true)
        ->set("paymentConfigs.{$this->gcash->id}.is_enabled", false)
        ->call('save');

    $config = $this->organization->fresh()->organizationPaymentMethods()
        ->where('payment_method_id', $this->gcash->id)
        ->first();

    expect($config->is_enabled)->toBeFalse();
});

it('allows superadmin to switch and manage any organization', function () {
    Auth::login($this->superAdmin);

    $anotherOrg = Organization::factory()->create([
        'name' => 'Iloilo Branch',
        'code' => 'ILO',
    ]);

    Livewire::test(Settings::class)
        ->call('changeOrganization', $anotherOrg->id)
        ->assertSet('name', 'Iloilo Branch')
        ->assertSet('code', 'ILO')
        ->set('name', 'Iloilo City Central')
        ->call('save');

    expect($anotherOrg->fresh()->name)->toBe('Iloilo City Central');
});
