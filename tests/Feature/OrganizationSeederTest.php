<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\RoleAndPermissionSeeder;

beforeEach(function () {
    $this->seed([
        RoleAndPermissionSeeder::class,
        PaymentMethodSeeder::class,
        OrganizationSeeder::class,
    ]);
});

it('seeds the global superadmin account with correct role', function () {
    $superadmin = User::where('email', 'superadmin@equilend.test')->first();

    expect($superadmin)->not->toBeNull()
        ->and($superadmin->name)->toBe('Super Admin')
        ->and($superadmin->hasRole('superadmin'))->toBeTrue();
});

it('seeds the 5 official university colleges', function () {
    $expectedColleges = [
        'CCS'  => 'College of Computer Studies',
        'CED'  => 'College of Education',
        'COET' => 'College of Engineering and Technology',
        'CSM'  => 'College of Science and Mathematics',
        'CASS' => 'College of Arts and Social Sciences',
    ];

    expect(Organization::count())->toBe(5);

    foreach ($expectedColleges as $code => $name) {
        $org = Organization::where('code', $code)->first();

        expect($org)->not->toBeNull()
            ->and($org->name)->toBe($name)
            ->and($org->is_active)->toBeTrue();
    }
});

it('seeds designated organization admins with proper role and pivot attachment', function () {
    $adminMap = [
        'CCS'  => ['email' => 'admin.ccs@equilend.test', 'name' => 'CCS College Admin'],
        'CED'  => ['email' => 'admin.ced@equilend.test', 'name' => 'CED College Admin'],
        'COET' => ['email' => 'admin.coet@equilend.test', 'name' => 'COET College Admin'],
        'CSM'  => ['email' => 'admin.csm@equilend.test', 'name' => 'CSM College Admin'],
        'CASS' => ['email' => 'admin.cass@equilend.test', 'name' => 'CASS College Admin'],
    ];

    foreach ($adminMap as $code => $expected) {
        $org = Organization::where('code', $code)->first();
        expect($org)->not->toBeNull();

        $admin = User::where('email', $expected['email'])->first();
        expect($admin)->not->toBeNull()
            ->and($admin->name)->toBe($expected['name'])
            ->and($admin->hasRole('organization_admin'))->toBeTrue();

        // Check pivot attachment in organization_members
        $memberPivot = $org->members()->where('user_id', $admin->id)->first();
        expect($memberPivot)->not->toBeNull()
            ->and($memberPivot->pivot->role)->toBe('admin');
    }
});

it('seeds sample member borrower accounts attached to each college', function () {
    $collegeCodes = ['CCS', 'CED', 'COET', 'CSM', 'CASS'];

    foreach ($collegeCodes as $code) {
        $org = Organization::where('code', $code)->first();
        expect($org)->not->toBeNull();

        $student = User::where('email', 'student.'.mb_strtolower($code).'@equilend.test')->first();
        expect($student)->not->toBeNull()
            ->and($student->hasRole('member'))->toBeTrue();

        // Check pivot attachment in organization_members
        $memberPivot = $org->members()->where('user_id', $student->id)->first();
        expect($memberPivot)->not->toBeNull()
            ->and($memberPivot->pivot->role)->toBe('member');
    }
});

it('configures gcash and maya payment channels for each college', function () {
    $gcash = PaymentMethod::where('payment_code', 'gcash')->first();
    $maya  = PaymentMethod::where('payment_code', 'maya')->first();

    expect($gcash)->not->toBeNull()
        ->and($maya)->not->toBeNull();

    $colleges = Organization::all();

    foreach ($colleges as $college) {
        $gcashConfig = $college->organizationPaymentMethods()
            ->where('payment_method_id', $gcash->id)
            ->first();

        expect($gcashConfig)->not->toBeNull()
            ->and($gcashConfig->is_enabled)->toBeTrue()
            ->and($gcashConfig->account_numbers)->not->toBeEmpty();

        $mayaConfig = $college->organizationPaymentMethods()
            ->where('payment_method_id', $maya->id)
            ->first();

        expect($mayaConfig)->not->toBeNull()
            ->and($mayaConfig->is_enabled)->toBeTrue()
            ->and($mayaConfig->account_numbers)->not->toBeEmpty();
    }
});
