<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Category;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure categories exist and get their IDs
        $categories = [
            'Janitorial Supplies' => Category::firstOrCreate(['category_name' => 'Janitorial Supplies'], ['description' => 'Cleaning and sanitizing products'])->category_id,
            'Pantry Supplies'     => Category::firstOrCreate(['category_name' => 'Pantry Supplies'], ['description' => 'Beverages and pantry items'])->category_id,
            'Office Supplies'     => Category::firstOrCreate(['category_name' => 'Office Supplies'], ['description' => 'Pens, paper, and office accessories'])->category_id,
            'Personal Care'       => Category::firstOrCreate(['category_name' => 'Personal Care'], ['description' => 'First aid and personal care items'])->category_id,
        ];

        $products = [
            // Janitorial Supplies
            ['name' => '70% Isoprophyl Alcohol w/ Moisturizer 1-Gallon', 'category' => 'Janitorial Supplies', 'price' => 375.00],
            ['name' => 'Alcogienic 70% Ethyl Alcohol 3.7L', 'category' => 'Janitorial Supplies', 'price' => 0.00],
            ['name' => 'Alcoshield 70% Isoprophyl Alcohol 500ml', 'category' => 'Janitorial Supplies', 'price' => 120.00],
            ['name' => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer 320ml', 'category' => 'Janitorial Supplies', 'price' => 114.29],
            ['name' => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer (Pump) 500ml', 'category' => 'Janitorial Supplies', 'price' => 156.00],
            ['name' => 'Doctor J 70% Isoprophyl Alcohol w/ Moisturizer (Spray Bottle) 320ml', 'category' => 'Janitorial Supplies', 'price' => 0.00],

            // Pantry Supplies
            ['name' => "Absolute Pure Distilled Drinking Water 35's 350ml", 'category' => 'Pantry Supplies', 'price' => 560.00],
            ['name' => "Absolute Pure Distilled Drinking Water 24's 500ml", 'category' => 'Pantry Supplies', 'price' => 552.00],
            ['name' => 'Evian Natural Mineral Water 500ml', 'category' => 'Pantry Supplies', 'price' => 0.00],
            ['name' => 'Evian Natural Spring Water 330ml', 'category' => 'Pantry Supplies', 'price' => 76.50],
            ['name' => 'Lightwater Electrolyte Enhanced Water 650ml', 'category' => 'Pantry Supplies', 'price' => 0.00],
            ['name' => "Nature's Spring Distilled Drinking Water 500ml", 'category' => 'Pantry Supplies', 'price' => 16.00],

            // Office Supplies
            ['name' => 'Panda Classique Water Gel Pen (0.7) Black / Blue / Red', 'category' => 'Office Supplies', 'price' => 7.00],
            ['name' => 'Panda Super Ballpen Rubber Grip Black / Blue / Red', 'category' => 'Office Supplies', 'price' => 9.00],
            ['name' => "Pentel Energel Sign Pen (0.5) 12's Blue", 'category' => 'Office Supplies', 'price' => 1764.00],
            ['name' => 'HBW Matrix OG-5 Oil Gel Pen Black / Blue / Red', 'category' => 'Office Supplies', 'price' => 7.00],
            ['name' => 'HBW Matrix OG-5 Oil Gel Pen 50\'s Black/Blue/Red', 'category' => 'Office Supplies', 'price' => 350.00],

            // Personal Care
            ['name' => 'Band-Aid Water Block Tough Strips', 'category' => 'Personal Care', 'price' => 132.00],
            ['name' => 'First Aid Kit', 'category' => 'Personal Care', 'price' => 525.00],
            ['name' => 'First Aid Kit (Hard Case) Red', 'category' => 'Personal Care', 'price' => 468.75],
            ['name' => 'Osha Ansi First Aid Kit (362items)', 'category' => 'Personal Care', 'price' => 2100.00],
        ];

        foreach ($products as $p) {
            $catId = $categories[$p['category']];

            Product::updateOrCreate(
                ['product_name' => $p['name']],
                [
                    'category_id'   => $catId,
                    'product_stocks'=> 0,
                    'description'   => null,
                    'price'         => $p['price'],
                    'isSale'        => false,
                ]
            );
        }
    }
}
