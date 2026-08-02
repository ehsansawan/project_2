<?php

namespace App\Repositories;

use App\Models\Service;
use App\Models\EmployeeService;
use App\Models\QueueTicket;
use Illuminate\Support\Collection;

class ServiceRepository
{
    public function getAll(): Collection
    {
        return Service::with('employees')->get();
    }

    public function findById(int $id): ?Service
    {
        return Service::with('employees')->find($id);
    }

    public function findByQrCode(string $qrCodeString): ?Service
    {
        return Service::where('qr_code_string', $qrCodeString)
            ->where('status', 'active')
            ->first();
    }

    public function create(array $data): Service
    {
        return Service::create($data);
    }

    public function update(Service $service, array $data): bool
    {
        return $service->update($data);
    }

    public function delete(Service $service): bool
    {
        return $service->delete();
    }

    public function assignEmployee(int $serviceId, int $employeeId): EmployeeService
    {
        return EmployeeService::firstOrCreate(
            ['service_id' => $serviceId, 'employee_id' => $employeeId],
            ['is_active' => true, 'assigned_at' => now()]
        );
    }

    public function unassignEmployee(int $serviceId, int $employeeId): bool
    {
        return EmployeeService::where('service_id', $serviceId)
            ->where('employee_id', $employeeId)
            ->delete();
    }

    public function getActiveWaitingCount(int $serviceId): int
    {
        return QueueTicket::where('service_id', $serviceId)
            ->where('status', 'waiting')
            ->count();
    }

    public function getCompletedTodayCount(int $serviceId): int
    {
        return QueueTicket::where('service_id', $serviceId)
            ->where('status', 'completed')
            ->whereDate('called_at', today())
            ->count();
    }
}