<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ComplainCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Roads & Potholes', 'weight' => 70.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Waste & Cleanliness', 'weight' => 50.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Streetlights & Electricity', 'weight' => 40.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Water & Sewage', 'weight' => 60.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Public Services', 'weight' => 50.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Other', 'weight' => 30.00, 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        DB::table('complain_categories')->insert($categories);
    }
}
