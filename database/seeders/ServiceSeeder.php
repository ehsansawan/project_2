<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Service;
use App\Models\EmployeeService;
use App\Models\User;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        $services = [
            [
                'name' => 'Official Documents',
                'prefix' => 'O',
                'qr_code_string' => 'SRV-OFFDOC-001',
                'estimated_time_minutes' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Building Permits',
                'prefix' => 'B',
                'qr_code_string' => 'SRV-BLDPRM-002',
                'estimated_time_minutes' => 15,
                'status' => 'active',
            ],
            [
                'name' => 'Financial Services',
                'prefix' => 'F',
                'qr_code_string' => 'SRV-FINSRV-003',
                'estimated_time_minutes' => 8,
                'status' => 'active',
            ],
            [
                'name' => 'Complaints Office',
                'prefix' => 'C',
                'qr_code_string' => 'SRV-CMPLNT-004',
                'estimated_time_minutes' => 12,
                'status' => 'active',
            ],
        ];

        foreach ($services as $serviceData) {
            Service::create($serviceData);
        }

        // $employees = User::where('role', 'employee')->get();
        // $services = Service::all();

        // if ($employees->count() > 0 && $services->count() > 0) {
        //     foreach ($employees as $index => $employee) {
        //         $serviceIndex = $index % $services->count();
        //         $service = $services[$serviceIndex];

        //         EmployeeService::create([
        //             'employee_id' => $employee->id,
        //             'service_id' => $service->id,
        //             'is_active' => true,
        //             'assigned_at' => now(),
        //         ]);
        //     }
        // }
    }
}