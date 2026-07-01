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
            ['name' => 'Roads & Potholes', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Waste & Cleanliness', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Streetlights & Electricity', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Water & Sewage', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Public Services', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Other', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        DB::table('complain_categories')->insert($categories);
    }
}
