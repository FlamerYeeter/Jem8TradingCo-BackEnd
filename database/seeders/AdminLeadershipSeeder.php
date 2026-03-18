<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\admin_leadership;

class AdminLeadershipSeeder extends Seeder
{
    public function run(): void
    {
        $leaders = [

            [
                'name' => 'Ms. Shella R. Acibar',
                'position' => 'Co-Owner of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Shella_Ricafrente-Acibar.png',
            ],
            [
                'name' => 'Ms. Jinkie Malinag',
                'position' => 'Co-Owner of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Jinkie_Ricafrente-Malinag.png',
            ],
            [
                'name' => 'Ms. Akiko Serrano',
                'position' => 'Sales Executive of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Akiko_Serrano.png',
            ],
            [
                'name' => 'Ms. Ruby Ann Castillo',
                'position' => 'Sales Executive of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Ruby_Ann_Castillo.png',
            ],
            [
                'name' => 'Ms. Charisse Mae Decano',
                'position' => 'Admin/HR Representative of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Charisse_Decano.png',
            ],
            [
                'name' => 'Mr. Adrian Mallanao',
                'position' => 'Laison Head Officer of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Adrian_Mallanao.png',
            ],
            [
                'name' => 'Mr. Vhernaldo Ricafrente',
                'position' => 'Marketing/Admin Assistant of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Vhernaldo_Ricafrente.png',
            ],
            [
                'name' => 'Mr. Mark Edward Malinag',
                'position' => 'Marketing/Admin Assistant of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Mark_Edward_C_Malinag.png',
            ],
            [
                'name' => 'Mr. Daniel Kian Rodriguez Cadena',
                'position' => 'Business Associate of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Daniel_Kian_Rodriguez_Cadena.png',
            ],
            [
                'name' => 'Ms. Kayla R. Bacsafra',
                'position' => 'Sales Executive of Jem 8 Circle (South Luzon Area)',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Kayla_R_Bacsafra.png',
            ],
            [
                'name' => 'Ms. Cristina A. Saturnio',
                'position' => 'Accounting and Finance of Jem 8 Circle',
                'status' => true,
                'leadership_img' => 'leadership_imgs/Cristina_A_Saturnio.png',
            ],

        ];

        foreach ($leaders as $leader) {
            admin_leadership::create($leader);
        }
    }
}