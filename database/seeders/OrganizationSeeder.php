<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);

        // 1. Create or update Superadmin account
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@equilend.test'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('superadmin');

        // 2. Create or update Organization Manager account
        $manager = User::firstOrCreate(
            ['email' => 'manager@equilend.test'],
            [
                'name'              => 'Makati Branch Manager',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $manager->assignRole('organization_admin');

        // 3. Create or update sample Organization
        $organization = Organization::firstOrCreate(
            ['code' => 'MKT'],
            [
                'name'        => 'EquiLend Makati Branch',
                'email'       => 'makati@equilend.test',
                'phone'       => '09171234567',
                'address'     => 'Ayala Avenue, Makati City, Metro Manila',
                'description' => 'EquiLend Makati Flagship Lending Branch',
                'is_active'   => true,
            ]
        );

        // Assign manager to the organization
        $organization->members()->syncWithoutDetaching([
            $manager->id => ['role' => 'admin'],
        ]);

        // 4. Configure payment methods for the organization
        $gcash = PaymentMethod::where('payment_code', 'gcash')->first();

        if ($gcash !== null) {
            $organization->organizationPaymentMethods()->firstOrCreate(
                ['payment_method_id' => $gcash->id],
                [
                    'account_name'    => 'EquiLend Makati Inc.',
                    'account_numbers' => ['09171112222', '09173334444'],
                    'is_enabled'      => true,
                ]
            );
        }

        $maya = PaymentMethod::where('payment_code', 'maya')->first();

        if ($maya !== null) {
            $organization->organizationPaymentMethods()->firstOrCreate(
                ['payment_method_id' => $maya->id],
                [
                    'account_name'    => 'EquiLend Makati Inc.',
                    'account_numbers' => ['09185556666'],
                    'is_enabled'      => true,
                ]
            );
        }
    }
}
