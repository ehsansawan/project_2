<?php

namespace App\Repositories;

use App\Models\QueueTicket;
use App\Models\Service;
use App\Models\EmployeeService;


class QueueRepository
{
    public function getServiceByQrCode(string $qrCode): ?Service
    {
        return Service::where('qr_code_string', $qrCode)
                      ->where('status', 'active')
                      ->first();
    }

    public function getServiceById(int $id): ?Service
    {
        return Service::find($id);
    }

    public function getLastTicketNumberToday(int $serviceId): int
    {
        return QueueTicket::where('service_id', $serviceId)
                          ->whereDate('joined_at', today())
                          ->max('current_number') ?? 0;
    }

    public function createTicket(array $data): QueueTicket
    {
        return QueueTicket::create($data);
    }

    public function getActiveTicketsCount(int $serviceId): int
    {
        return QueueTicket::where('service_id', $serviceId)
                          ->where('status', 'waiting')
                          ->count();
    }

    public function isEmployeeAssigned(int $employeeId, int $serviceId): bool
    {
        return EmployeeService::where('employee_id', $employeeId)
            ->where('service_id', $serviceId)
            ->where('is_active', true)
            ->exists();
    }

    public function getCurrentlyServing(int $serviceId): ?\App\Models\QueueTicket
    {
        $ticket = QueueTicket::where('service_id', $serviceId)
        ->where('status', 'serving')
        ->first();
    
         return $ticket ? $ticket->append('display_number')->makeHidden(['service']) : null;
    }

    public function getWaitingList(int $serviceId)
    {
        return \App\Models\QueueTicket::where('service_id', $serviceId)
        ->where('status', 'waiting')
        ->orderBy('current_number', 'asc')
        ->get()
        ->append('display_number')->makeHidden(['service']);
    }

    public function getNoShowList(int $serviceId)
    {
        return \App\Models\QueueTicket::where('service_id', $serviceId)
        ->where('status', 'no_show')
        ->orderBy('updated_at', 'desc')
        ->get()
        ->append('display_number')->makeHidden(['service']);
    }

    public function getCompletedTodayCount(int $serviceId): int
    {
        return \App\Models\QueueTicket::where('service_id', $serviceId)
            ->where('status', 'completed')
            ->whereDate('updated_at', today())
            ->count();
    }

    public function getAssignedServices(int $employeeId): array
    {
        $serviceIds = EmployeeService::where('employee_id', $employeeId)
            ->where('is_active', 1)
            ->pluck('service_id');

        $services = Service::whereIn('id', $serviceIds)->get();

        return $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'prefix' => $service->prefix,
                'qr_code_string' => $service->qr_code_string,
                'estimated_time_minutes' => $service->estimated_time_minutes,
                'status' => $service->status,
                'people_waiting' => QueueTicket::where('service_id', $service->id)
                    ->where('status', 'waiting')
                    ->count(),
                'served_today' => QueueTicket::where('service_id', $service->id)
                    ->where('status', 'completed')
                    ->whereDate('updated_at', today())
                    ->count(),
            ];
        })->toArray();
    }
}