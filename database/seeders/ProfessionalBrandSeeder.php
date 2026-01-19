<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Brand;

class ProfessionalBrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all professional users
        $professionals = User::where('role', 'professional')->get();

        // Get all brand IDs
        $brandIds = Brand::pluck('id')->toArray();

        foreach ($professionals as $professional) {

            // Random 3–6 brands per professional
            $randomBrands = collect($brandIds)
                ->shuffle()
                ->take(rand(3, 6));

            foreach ($randomBrands as $brandId) {
                DB::table('professional_brands')->insert([
                    'user_id'    => $professional->id,
                    'brand_id'   => $brandId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
