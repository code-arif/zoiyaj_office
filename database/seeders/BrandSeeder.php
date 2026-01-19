<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            'L\'Oreal',
            'Maybelline',
            'Revlon',
            'MAC Cosmetics',
            'NARS',
            'Estée Lauder',
            'Dior',
            'Chanel',
            'Sephora Collection',
            'NYX Professional Makeup',
            'Urban Decay',
            'Clinique',
            'Shiseido',
            'Benefit Cosmetics',
            'Too Faced'
        ];

        foreach ($brands as $brand) {
            DB::table('brands')->insert([
                'name'       => $brand,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
