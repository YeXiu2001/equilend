<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            PaymentMethodSeeder::class,
            OrganizationSeeder::class,
        ]);

        if (User::count() <= 2) {
            User::factory(10)->create();
        }
    }
}
