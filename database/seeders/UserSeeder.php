<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Default users
        User::create([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'user@gmail.com',
            'role'       => 'seller',
            'password'   => Hash::make('12345678'),
        ]);

        User::create([
            'first_name' => 'Admin',
            'last_name'  => 'User',
            'email'      => 'admin@gmail.com',
            'role'       => 'admin',
            'password'   => Hash::make('12345678'),
        ]);

        // -----------------------------
        // 30 Professional Users
        // -----------------------------
        for ($i = 1; $i <= 30; $i++) {
            User::create([
                'first_name' => 'Professional',
                'last_name'  => 'User ' . $i,
                'email'      => "professional{$i}@example.com",
                'phone_number' => '017000000' . str_pad($i, 2, '0', STR_PAD_LEFT),

                'role'       => 'professional',
                'status'     => 'active',

                'professional_name'  => 'Pro Business ' . $i,
                'professional_phone' => '018000000' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'professional_email' => "pro{$i}@business.com",

                'address'    => 'House 10, Road ' . $i,
                'city'       => 'Dhaka',
                'state'      => 'Dhaka',
                'country'    => 'Bangladesh',
                'postal_code' => '120' . $i,

                'bio'        => 'This is a demo professional user profile.',
                'years_in_business' => rand(1, 10),

                'is_premium' => rand(0, 1),
                'is_sell_retail_products' => rand(0, 1),
                'is_promo_participation'  => rand(0, 1),

                'password'   => Hash::make('12345678'),
                'remember_token' => Str::random(10),
            ]);
        }
    }
}
