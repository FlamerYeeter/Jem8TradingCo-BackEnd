<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('categories')->insert([
            [
                'category_name' => 'Office Supplies, Stationery & Equipment',
                'description'   => 'Office essentials including stationery, printing supplies, and office equipment.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_name' => 'Pantry Supplies',
                'description'   => 'Food and beverage items for office or home pantry use.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_name' => 'Janitorial Supplies',
                'description'   => 'Cleaning and sanitation products for maintaining cleanliness.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_name' => 'Personal & Home Care Products',
                'description'   => 'Personal hygiene and home care essentials.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_name' => 'Customized Items for Giveaways',
                'description'   => 'Personalized and branded items suitable for events and giveaways.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
            [
                'category_name' => 'Health & Wellness Products',
                'description'   => 'Products promoting health, wellness, and personal well-being.',
                'created_at'    => now(),
                'updated_at'    => now(),
            ],
        ]);
    }
}