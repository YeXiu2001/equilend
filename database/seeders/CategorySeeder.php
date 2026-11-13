<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name'        => 'Computing Devices & Laptops',
                'description' => 'Laptops, mobile workstations, tablets, VR headsets, and computer peripherals.',
            ],
            [
                'name'        => 'Audio & Visual Equipment',
                'description' => 'Projectors, display monitors, sound systems, microphones, and production lighting.',
            ],
            [
                'name'        => 'Laboratory & Scientific Instruments',
                'description' => 'Precision balances, microscopes, centrifuges, meters, and specialized testing equipment.',
            ],
            [
                'name'        => 'Electronics, Microcontrollers & Robotics',
                'description' => 'Development kits, oscilloscopes, multimeters, power supplies, and sensors.',
            ],
            [
                'name'        => 'Surveying & Field Hardware',
                'description' => 'Digital total stations, tripods, GPS locators, and environmental surveying tools.',
            ],
        ];

        foreach ($categories as $data) {
            $slug = Str::slug($data['name']);

            Category::updateOrCreate(
                [
                    'slug'            => $slug,
                    'organization_id' => null,
                ],
                [
                    'name'        => $data['name'],
                    'description' => $data['description'],
                ]
            );
        }
    }
}
