<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ProfessionalWorkingHoursSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $days = [
            'monday',
            'tuesday',
            'wednesday',
            'thursday',
            'friday',
            'saturday',
            'sunday',
        ];

        $professionals = User::where('role', 'professional')->get();

        foreach ($professionals as $professional) {

            foreach ($days as $day) {

                // Randomly decide closed or open
                $isClosed = rand(0, 10) < 2; // ~20% closed

                DB::table('professinal_working_hours')->insert([
                    'user_id'    => $professional->id,
                    'day'        => $day,
                    'is_closed'  => $isClosed,
                    'open_time'  => $isClosed ? null : '09:00:00',
                    'close_time' => $isClosed ? null : '18:00:00',
                    'created_at'=> now(),
                    'updated_at'=> now(),
                ]);
            }
        }
    }
}
