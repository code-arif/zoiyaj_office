<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfessionalServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            'Basic Consultation',
            'Premium Consultation',
            'Full Service Package',
            'Maintenance Service',
            'Emergency Support',
            'Custom Service',
        ];

        $durations = ['30 mins', '45 mins', '1 hour', '1.5 hours', '2 hours'];

        $professionals = User::where('role', 'professional')->get();

        foreach ($professionals as $professional) {

            $serviceCount = rand(3, 6);

            for ($i = 0; $i < $serviceCount; $i++) {
                DB::table('professinal_services')->insert([
                    'user_id' => $professional->id,
                    'name' => $services[array_rand($services)],
                    'starting_price' => rand(500, 5000),
                    'duration' => $durations[array_rand($durations)],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
