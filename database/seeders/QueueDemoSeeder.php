<?php

namespace Database\Seeders;

use App\Models\EmployeeService;
use App\Models\QueueTicket;
use App\Models\QueueTicketArchive;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QueueDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('queue_tickets_archive')->truncate();
        DB::table('queue_tickets')->truncate();
        DB::table('employee_services')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $services = Service::all();
        $admins = User::role('admin')->get();

        // Assign each admin (acting as an employee) to one or two services.
        foreach ($admins as $index => $admin) {
            EmployeeService::create([
                'employee_id' => $admin->id,
                'service_id' => $services[$index % $services->count()]->id,
                'is_active' => true,
                'assigned_at' => now()->subMonths(2),
            ]);
        }

        $statuses = ['waiting', 'waiting', 'serving', 'completed', 'completed', 'no_show'];
        $names = ['Ahmad Khalil', 'Layla Hassan', 'Omar Youssef', 'Rania Saleh', 'Karim Nasser', 'Dana Ibrahim'];

        foreach ($services as $service) {
            $number = 1;

            foreach ($statuses as $i => $status) {
                $ticket = QueueTicket::create([
                    'user_name' => $names[$i],
                    'current_number' => $number++,
                    'service_id' => $service->id,
                    'status' => $status,
                    'is_manual' => $i % 3 === 0,
                    'served_by_employee_id' => in_array($status, ['serving', 'completed'], true) ? $admins->first()->id : null,
                    'joined_at' => now()->subHours(6 - $i),
                    'called_at' => in_array($status, ['serving', 'completed', 'no_show'], true) ? now()->subHours(5 - $i) : null,
                ]);

                if ($status === 'completed') {
                    QueueTicketArchive::create([
                        'original_ticket_id' => $ticket->id,
                        'service_id' => $service->id,
                        'user_name' => $ticket->user_name,
                        'current_number' => $ticket->current_number,
                        'status' => $ticket->status,
                        'archival_reason' => 'served',
                        'is_manual' => $ticket->is_manual,
                        'joined_at' => $ticket->joined_at,
                        'called_at' => $ticket->called_at,
                        'served_at' => $ticket->called_at,
                        'served_by_employee_id' => $ticket->served_by_employee_id,
                        'archived_at' => now()->subHours(1),
                    ]);
                }
            }
        }
    }
}
