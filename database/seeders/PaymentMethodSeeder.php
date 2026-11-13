<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $methods = [
            [
                'payment_code' => 'cash',
                'name'         => 'Cash',
                'description'  => 'Over-the-counter Cash Payment at Circulation Desk',
                'is_active'    => true,
            ],
            [
                'payment_code' => 'gcash',
                'name'         => 'GCash',
                'description'  => 'GCash Mobile Wallet',
                'is_active'    => true,
            ],
            [
                'payment_code' => 'maya',
                'name'         => 'Maya',
                'description'  => 'Maya Digital Wallet & Bank',
                'is_active'    => true,
            ],
            [
                'payment_code' => 'gotyme',
                'name'         => 'GoTyme',
                'description'  => 'GoTyme Digital Bank',
                'is_active'    => true,
            ],
        ];

        foreach ($methods as $method) {
            PaymentMethod::firstOrCreate(
                ['payment_code' => $method['payment_code']],
                $method
            );
        }
    }
}
