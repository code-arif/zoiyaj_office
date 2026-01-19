<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Specialty;

class ProfessionalSpecialtySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // All professional users
        $professionals = User::where('role', 'professional')->get();


        // All specialties IDs
        $specialtyIds = Specialty::pluck('id')->toArray();

        foreach ($professionals as $professional) {

            // Random 4–5 specialties per professional
            $randomSpecialties = collect($specialtyIds)
                ->shuffle()
                ->take(rand(4, 5));

            foreach ($randomSpecialties as $specialtyId) {
                DB::table('professional_specialties')->insert([
                    'user_id'      => $professional->id,
                    'specialty_id' => $specialtyId,
                    'created_at'   => now(),
                    'updated_at'   => now(),
                ]);
            }
        }
    }
}
