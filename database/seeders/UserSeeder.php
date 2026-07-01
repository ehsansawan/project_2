<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'first_name' => 'Ahmad',
                'last_name' => 'Citizen',
                'email' => 'ahmad@gmail.com',
                'phone' => '0991234567',
                'national_id' => '12345678901',
                'password' => Hash::make('password123'),
                'birth_date' => '1990-01-01',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'first_name' => 'Sara',
                'last_name' => 'Unverified',
                'email' => 'sara@gmail.com',
                'phone' => '0992345678',
                'national_id' => '12345678902',
                'password' => Hash::make('password123'),
                'birth_date' => '1995-05-05',
                'email_verified_at' => null,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Employee',
                'email' => 'omar@gmail.com',
                'phone' => '0993456789',
                'national_id' => '12345678903',
                'password' => Hash::make('password123'),
                'birth_date' => '1985-10-10',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'first_name' => 'Mohammed',
                'last_name' => 'Citizen',
                'email' => 'mohammed@gmail.com',
                'phone' => '0991254567',
                'national_id' => '12345678961',
                'password' => Hash::make('password123'),
                'birth_date' => '1990-01-01',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'first_name' => 'Ali',
                'last_name' => 'Citizen',
                'email' => 'ali@gmail.com',
                'phone' => '0991234667',
                'national_id' => '12345678301',
                'password' => Hash::make('password123'),
                'birth_date' => '1990-01-01',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'first_name' => 'maia',
                'last_name' => 'Citizen',
                'email' => 'maia@gmail.com',
                'phone' => '0991234767',
                'national_id' => '12325678901',
                'password' => Hash::make('password123'),
                'birth_date' => '1990-01-01',
                'email_verified_at' => Carbon::now(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('users')->insert($users);

        $profiles = [
            [
                'user_id' => 1,
                'profile_name' => 'Ahmad Citizen',
                'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 2,
                'profile_name' => 'Sara Unverified',
                'status' => 'unverified',
                'citizenship_score' => 0,
                'credibility_score' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 3,
                'profile_name' => 'Omar Employee',
                'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 4,
                'profile_name' => 'Mohammed Citizen',
                'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 5,
                'profile_name' => 'Ali Citizen',
                'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'user_id' => 6,
                'profile_name' => 'maia Citizen',
                'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];

        DB::table('profiles')->insert($profiles);
    }
}