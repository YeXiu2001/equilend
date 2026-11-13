<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\PaymentMethod;
use Illuminate\Database\QueryException;
use App\Models\OrganizationPaymentMethod;

test('organization can be created with basic details', function () {
    $organization = Organization::factory()->create([
        'name'        => 'EquiLend Main',
        'code'        => 'EQ-01',
        'email'       => 'info@equilend.test',
        'phone'       => '09171234567',
        'address'     => 'Makati City',
        'description' => 'EquiLend Main Branch',
        'is_active'   => true,
    ]);

    expect($organization->name)->toBe('EquiLend Main')
        ->and($organization->code)->toBe('EQ-01')
        ->and($organization->is_active)->toBeTrue();
});

test('organization can configure payment methods with multiple account numbers and same account name', function () {
    $organization = Organization::factory()->create();
    $gcash        = PaymentMethod::factory()->create([
        'payment_code' => 'gcash',
        'name'         => 'GCash',
    ]);

    $config = OrganizationPaymentMethod::create([
        'organization_id'   => $organization->id,
        'payment_method_id' => $gcash->id,
        'account_name'      => 'EquiLend Lending Corp',
        'account_numbers'   => ['09171112222', '09183334444', '09195556666'],
        'is_enabled'        => true,
    ]);

    expect($config->account_name)->toBe('EquiLend Lending Corp')
        ->and($config->account_numbers)->toBeArray()
        ->and($config->account_numbers)->toHaveCount(3)
        ->and($config->account_numbers)->toContain('09171112222')
        ->and($config->is_enabled)->toBeTrue();

    // Verify relationship via Organization model
    $orgPaymentMethods = $organization->fresh()->organizationPaymentMethods;
    expect($orgPaymentMethods)->toHaveCount(1)
        ->and($orgPaymentMethods->first()->paymentMethod->name)->toBe('GCash');
});

test('organization payment method can be enabled and disabled', function () {
    $config = OrganizationPaymentMethod::factory()->create([
        'is_enabled' => true,
    ]);

    expect($config->is_enabled)->toBeTrue();

    $config->update(['is_enabled' => false]);
    expect($config->fresh()->is_enabled)->toBeFalse();

    // Test enabled scope
    expect(OrganizationPaymentMethod::enabled()->where('id', $config->id)->exists())->toBeFalse();

    $config->update(['is_enabled' => true]);
    expect(OrganizationPaymentMethod::enabled()->where('id', $config->id)->exists())->toBeTrue();
});

test('organization cannot have duplicate configurations for the same payment method', function () {
    $organization  = Organization::factory()->create();
    $paymentMethod = PaymentMethod::factory()->create();

    OrganizationPaymentMethod::create([
        'organization_id'   => $organization->id,
        'payment_method_id' => $paymentMethod->id,
        'account_name'      => 'Main Account',
        'account_numbers'   => ['09171112222'],
        'is_enabled'        => true,
    ]);

    expect(function () use ($organization, $paymentMethod) {
        OrganizationPaymentMethod::create([
            'organization_id'   => $organization->id,
            'payment_method_id' => $paymentMethod->id,
            'account_name'      => 'Duplicate Account',
            'account_numbers'   => ['09183334444'],
            'is_enabled'        => true,
        ]);
    })->toThrow(QueryException::class);
});
