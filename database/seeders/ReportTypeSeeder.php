<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types =[
            ['name' => 'Violent Content', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Immoral Content', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Misleading Information', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Offensive Content', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Irrelevant Content', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
            ['name' => 'Other', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()],
        ];

        DB::table('report_types')->insert($types);
    }
}
