<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Fetch category IDs by name
        $categories = DB::table('categories')
            ->whereIn('category_name', [
                'Janitorial Supplies',
                'Pantry Supplies',
                'Office Supplies, Stationery & Equipment',
                'Personal & Home Care Products',
            ])
            ->pluck('category_id', 'category_name');

        $products = [
            // Janitorial Supplies
            [
                'product_name'   => '70% Isoprophyl Alcohol w/ Moisturizer 1-Gallon',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 375.00,
            ],
            [
                'product_name'   => 'Alcogienic 70% Ethyl Alcohol 3.7L',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 0.00,
            ],
            [
                'product_name'   => 'Alcoshield 70% Isoprophyl Alcohol 500ml',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 120.00,
            ],
            [
                'product_name'   => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer 320ml',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 114.29,
            ],
            [
                'product_name'   => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer (Pump) 500ml',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 156.00,
            ],
            [
                'product_name'   => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer (Spray Bottle) 320ml',
                'category_name'  => 'Janitorial Supplies',
                'price'          => 0.00,
            ],

            // Pantry Supplies
            [
                'product_name'   => "Absolute Pure Distilled Drinking Water 35's 350ml",
                'category_name'  => 'Pantry Supplies',
                'price'          => 560.00,
            ],
            [
                'product_name'   => "Absolute Pure Distilled Drinking Water 24's 500ml",
                'category_name'  => 'Pantry Supplies',
                'price'          => 552.00,
            ],
            [
                'product_name'   => 'Evian Natural Mineral Water 500ml',
                'category_name'  => 'Pantry Supplies',
                'price'          => 0.00,
            ],
            [
                'product_name'   => 'Evian Natural Spring Water 330ml',
                'category_name'  => 'Pantry Supplies',
                'price'          => 76.50,
            ],
            [
                'product_name'   => 'Lightwater Electrolyte Enhanced Water 650ml',
                'category_name'  => 'Pantry Supplies',
                'price'          => 0.00,
            ],
            [
                'product_name'   => "Nature's Spring Distilled Drinking Water 500ml",
                'category_name'  => 'Pantry Supplies',
                'price'          => 16.00,
            ],

            // Office Supplies, Stationery & Equipment
            [
                'product_name'   => 'Panda Classique Water Gel Pen (0.7) Black / Blue / Red',
                'category_name'  => 'Office Supplies, Stationery & Equipment',
                'price'          => 7.00,
            ],
            [
                'product_name'   => 'Panda Super Ballpen Rubber Grip Black / Blue / Red',
                'category_name'  => 'Office Supplies, Stationery & Equipment',
                'price'          => 9.00,
            ],
            [
                'product_name'   => "Pentel Energel Sign Pen (0.5) 12's Blue",
                'category_name'  => 'Office Supplies, Stationery & Equipment',
                'price'          => 1764.00,
            ],
            [
                'product_name'   => 'HBW Matrix OG-5 Oil Gel Pen Black / Blue / Red',
                'category_name'  => 'Office Supplies, Stationery & Equipment',
                'price'          => 7.00,
            ],
            [
                'product_name'   => "HBW Matrix OG-5 Oil Gel Pen 50's Black/Blue/Red",
                'category_name'  => 'Office Supplies, Stationery & Equipment',
                'price'          => 350.00,
            ],

            // Personal & Home Care Products
            [
                'product_name'   => 'Band-Aid Water Block Tough Strips',
                'category_name'  => 'Personal & Home Care Products',
                'price'          => 132.00,
            ],
            [
                'product_name'   => 'First Aid Kit',
                'category_name'  => 'Personal & Home Care Products',
                'price'          => 525.00,
            ],
            [
                'product_name'   => 'First Aid Kit (Hard Case) Red',
                'category_name'  => 'Personal & Home Care Products',
                'price'          => 468.75,
            ],
            [
                'product_name'   => 'Osha Ansi First Aid Kit (362items)',
                'category_name'  => 'Personal & Home Care Products',
                'price'          => 2100.00,
            ],
        ];

        $rows = array_map(function ($product) use ($categories) {
            return [
                'product_name'   => $product['product_name'],
                'category_id'    => $categories[$product['category_name']],
                
                'description'    => null,
                'price'          => $product['price'],
                'isSale'         => false,
                'created_at'     => now(),
                'updated_at'     => now(),
            ];
        }, $products);

        DB::table('products')->insert($rows);
    }
}
