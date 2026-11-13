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
        // 1. Ensure core roles exist
        Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'organization_admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'member', 'guard_name' => 'web']);

        // 2. Create or update Super Admin account
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@equilend.test'],
            [
                'name'              => 'Super Admin',
                'password'          => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole('superadmin');

        // Fetch payment methods
        $gcash = PaymentMethod::where('payment_code', 'gcash')->first();
        $maya  = PaymentMethod::where('payment_code', 'maya')->first();

        // 3. Define the 5 Official University Colleges
        $colleges = [
            [
                'name'        => 'College of Computer Studies',
                'code'        => 'CCS',
                'email'       => 'ccs@equilend.test',
                'phone'       => '09171234501',
                'address'     => 'CCS Building, Main Campus',
                'description' => 'College of Computer Studies - Equipment Lending Hub',
                'admin_name'  => 'CCS College Admin',
                'admin_email' => 'admin.ccs@equilend.test',
                'students'    => [
                    ['name' => 'CCS Student Borrower', 'email' => 'student.ccs@equilend.test'],
                    ['name' => 'CCS Student Borrower 2', 'email' => 'student2.ccs@equilend.test'],
                ],
                'gcash_acc' => '09171112222',
                'maya_acc'  => '09185556666',
            ],
            [
                'name'        => 'College of Education',
                'code'        => 'CED',
                'email'       => 'ced@equilend.test',
                'phone'       => '09171234502',
                'address'     => 'CED Building, Main Campus',
                'description' => 'College of Education - Equipment Lending Hub',
                'admin_name'  => 'CED College Admin',
                'admin_email' => 'admin.ced@equilend.test',
                'students'    => [
                    ['name' => 'CED Student Borrower', 'email' => 'student.ced@equilend.test'],
                    ['name' => 'CED Student Borrower 2', 'email' => 'student2.ced@equilend.test'],
                ],
                'gcash_acc' => '09171113333',
                'maya_acc'  => '09185557777',
            ],
            [
                'name'        => 'College of Engineering and Technology',
                'code'        => 'COET',
                'email'       => 'coet@equilend.test',
                'phone'       => '09171234503',
                'address'     => 'COET Complex, Main Campus',
                'description' => 'College of Engineering and Technology - Equipment Lending Hub',
                'admin_name'  => 'COET College Admin',
                'admin_email' => 'admin.coet@equilend.test',
                'students'    => [
                    ['name' => 'COET Student Borrower', 'email' => 'student.coet@equilend.test'],
                    ['name' => 'COET Student Borrower 2', 'email' => 'student2.coet@equilend.test'],
                ],
                'gcash_acc' => '09171114444',
                'maya_acc'  => '09185558888',
            ],
            [
                'name'        => 'College of Science and Mathematics',
                'code'        => 'CSM',
                'email'       => 'csm@equilend.test',
                'phone'       => '09171234504',
                'address'     => 'CSM Science Complex, Main Campus',
                'description' => 'College of Science and Mathematics - Equipment Lending Hub',
                'admin_name'  => 'CSM College Admin',
                'admin_email' => 'admin.csm@equilend.test',
                'students'    => [
                    ['name' => 'CSM Student Borrower', 'email' => 'student.csm@equilend.test'],
                    ['name' => 'CSM Student Borrower 2', 'email' => 'student2.csm@equilend.test'],
                ],
                'gcash_acc' => '09171115555',
                'maya_acc'  => '09185559999',
            ],
            [
                'name'        => 'College of Arts and Social Sciences',
                'code'        => 'CASS',
                'email'       => 'cass@equilend.test',
                'phone'       => '09171234505',
                'address'     => 'CASS Hall, Main Campus',
                'description' => 'College of Arts and Social Sciences - Equipment Lending Hub',
                'admin_name'  => 'CASS College Admin',
                'admin_email' => 'admin.cass@equilend.test',
                'students'    => [
                    ['name' => 'CASS Student Borrower', 'email' => 'student.cass@equilend.test'],
                    ['name' => 'CASS Student Borrower 2', 'email' => 'student2.cass@equilend.test'],
                ],
                'gcash_acc' => '09171116666',
                'maya_acc'  => '09185550000',
            ],
        ];

        foreach ($colleges as $data) {
            // Create or update College Organization
            $organization = Organization::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'        => $data['name'],
                    'email'       => $data['email'],
                    'phone'       => $data['phone'],
                    'address'     => $data['address'],
                    'description' => $data['description'],
                    'is_active'   => true,
                ]
            );

            // Create or update College Admin User
            $adminUser = User::firstOrCreate(
                ['email' => $data['admin_email']],
                [
                    'name'              => $data['admin_name'],
                    'password'          => Hash::make('password'),
                    'email_verified_at' => now(),
                ]
            );
            $adminUser->syncRoles(['organization_admin']);

            // Attach Admin to College with 'admin' pivot role
            $organization->members()->syncWithoutDetaching([
                $adminUser->id => ['role' => 'admin'],
            ]);

            // Create or update Student / Borrower Users
            foreach ($data['students'] as $studentData) {
                $studentUser = User::firstOrCreate(
                    ['email' => $studentData['email']],
                    [
                        'name'              => $studentData['name'],
                        'password'          => Hash::make('password'),
                        'email_verified_at' => now(),
                    ]
                );
                $studentUser->syncRoles(['member']);

                // Attach Student to College with 'member' pivot role
                $organization->members()->syncWithoutDetaching([
                    $studentUser->id => ['role' => 'member'],
                ]);
            }

            // Configure GCash payment method
            if ($gcash !== null) {
                $organization->organizationPaymentMethods()->updateOrCreate(
                    ['payment_method_id' => $gcash->id],
                    [
                        'account_name'    => "{$data['name']} Fund",
                        'account_numbers' => [$data['gcash_acc']],
                        'is_enabled'      => true,
                    ]
                );
            }

            // Configure Maya payment method
            if ($maya !== null) {
                $organization->organizationPaymentMethods()->updateOrCreate(
                    ['payment_method_id' => $maya->id],
                    [
                        'account_name'    => "{$data['name']} Fund",
                        'account_numbers' => [$data['maya_acc']],
                        'is_enabled'      => true,
                    ]
                );
            }
        }
    }
}
