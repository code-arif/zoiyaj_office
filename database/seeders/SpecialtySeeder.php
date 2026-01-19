<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $specialties = [
            'Hair Styling',
            'Hair Coloring',
            'Makeup Artist',
            'Bridal Makeup',
            'Skin Care',
            'Facial Treatment',
            'Nail Art',
            'Manicure & Pedicure',
            'Massage Therapy',
            'Spa Therapy',
            'Waxing Service',
            'Threading',
            'Beard Grooming',
            'Men Haircut',
            'Women Haircut',
            'Hair Treatment',
            'Body Scrub',
            'Body Massage',
            'Aromatherapy',
            'Beauty Consultation',
        ];

        foreach ($specialties as $specialty) {
            DB::table('specialties')->insert([
                'name'       => $specialty,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
