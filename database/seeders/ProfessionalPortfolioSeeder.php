<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProfessionalPortfolioSeeder extends Seeder
{
    public function run(): void
    {
        $professionals = User::where('role', 'professional')->get();

        foreach ($professionals as $professional) {

            $portfolioCount = rand(5, 8);

            for ($i = 1; $i <= $portfolioCount; $i++) {

                $type = rand(0, 10) > 7 ? 'video' : 'image';

                DB::table('professional_portfolios')->insert([
                    'user_id' => $professional->id,
                    'type'    => $type,
                    'name'    => ucfirst($type) . ' Portfolio ' . $i,
                    'image'   => $type === 'image'
                        ? 'portfolios/images/sample-' . rand(1, 5) . '.jpg'
                        : null,
                    'video'   => $type === 'video'
                        ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'
                        : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
