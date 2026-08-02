<?php

namespace App\Services;

use App\Repositories\ServiceRepository;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ServiceService
{
    private ServiceRepository $serviceRepository;

    public function __construct(ServiceRepository $serviceRepository)
    {
        $this->serviceRepository = $serviceRepository;
    }

    public function getAllServices(): array
    {
        $services = $this->serviceRepository->getAll();
        
        $servicesData = $services->map(function ($service) {
            return [
                'id' => $service->id,
                'name' => $service->name,
                'prefix' => $service->prefix,
                'qr_code_string' => $service->qr_code_string,
                'estimated_time_minutes' => $service->estimated_time_minutes,
                'status' => $service->status,
                'assigned_employees' => $service->employees->count(),
                'people_waiting' => $this->serviceRepository->getActiveWaitingCount($service->id),
                'served_today' => $this->serviceRepository->getCompletedTodayCount($service->id),
            ];
        });

        return [
            'data' => $servicesData,
            'message' => 'Services retrieved successfully',
            'code' => 200,
        ];
    }

    public function getServiceById(int $id): array
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundHttpException('Service not found');
        }

        return [
            'data' => [
                'service' => $service,
                'people_waiting' => $this->serviceRepository->getActiveWaitingCount($service->id),
                'served_today' => $this->serviceRepository->getCompletedTodayCount($service->id),
            ],
            'message' => 'Service details retrieved successfully',
            'code' => 200,
        ];
    }

    public function createService(array $validatedData): array
    {
        $validatedData['qr_code_string'] = $this->generateQrCodeString();
        
        $service = $this->serviceRepository->create($validatedData);

        return [
            'data' => $service,
            'message' => 'Service created successfully',
            'code' => 201,
        ];
    }

    public function updateService(int $id, array $validatedData): array
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundHttpException('Service not found');
        }

        $this->serviceRepository->update($service, $validatedData);

        return [
            'data' => $service->fresh(),
            'message' => 'Service updated successfully',
            'code' => 200,
        ];
    }

    public function deleteService(int $id): array
    {
        $service = $this->serviceRepository->findById($id);

        if (!$service) {
            throw new NotFoundHttpException('Service not found');
        }

        $this->serviceRepository->delete($service);

        return [
            'data' => null,
            'message' => 'Service deleted successfully',
            'code' => 200,
        ];
    }

    public function assignEmployee(int $serviceId, int $employeeId): array
    {
        $service = $this->serviceRepository->findById($serviceId);

        if (!$service) {
            throw new NotFoundHttpException('Service not found');
        }

        $assignment = $this->serviceRepository->assignEmployee($serviceId, $employeeId);

        return [
            'data' => $assignment,
            'message' => 'Employee assigned to service successfully',
            'code' => 200,
        ];
    }

    public function unassignEmployee(int $serviceId, int $employeeId): array
    {
        $this->serviceRepository->unassignEmployee($serviceId, $employeeId);

        return [
            'data' => null,
            'message' => 'Employee unassigned from service successfully',
            'code' => 200,
        ];
    }

    public function getServiceByQrCode(string $qrCodeString): array
    {
        $service = $this->serviceRepository->findByQrCode($qrCodeString);

        if (!$service) {
            throw new NotFoundHttpException('Invalid or inactive QR code');
        }

        $waitingCount = $this->serviceRepository->getActiveWaitingCount($service->id);
        $estimatedWaitTime = $waitingCount * $service->estimated_time_minutes;

        return [
            'data' => [
                'service' => [
                    'id' => $service->id,
                    'name' => $service->name,
                    'prefix' => $service->prefix,
                    'estimated_time_minutes' => $service->estimated_time_minutes,
                ],
                'people_waiting' => $waitingCount,
                'estimated_wait_time_minutes' => $estimatedWaitTime,
            ],
            'message' => 'Queue information retrieved successfully',
            'code' => 200,
        ];
    }

    private function generateQrCodeString(): string
    {
        return 'SRV-' . strtoupper(Str::random(8)) . '-' . time();
    }
}