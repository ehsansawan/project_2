<?php

namespace App\Services;

use App\Repositories\QueueRepository;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class QueueService
{
    private QueueRepository $queueRepository;
    private GeofencingService $geofencingService;

    public function __construct(QueueRepository $queueRepository, GeofencingService $geofencingService)
    {
        $this->queueRepository = $queueRepository;
        $this->geofencingService = $geofencingService;
    }

    public function getServiceDetails(int $serviceId): array
    {
        $service = $this->queueRepository->getServiceById($serviceId);

        if (!$service) {
            throw new NotFoundHttpException('Service not found');
        }

        $waitingCount = $this->queueRepository->getActiveTicketsCount($serviceId);
        $estimatedWaitTime = $waitingCount * $service->estimated_time_minutes;

        return [
            'data' => [
                'service' => $service,
                'people_waiting' => $waitingCount,
                'estimated_wait_time_minutes' => $estimatedWaitTime,
            ],
            'message' => 'Service details retrieved successfully',
            'code' => 200,
        ];
    }

    public function joinQueue(array $validatedData): array
    {
        $isAllowed = $this->geofencingService->isWithinMunicipality(
            $validatedData['latitude'], 
            $validatedData['longitude']
        );

        if (!$isAllowed) {
            throw new AccessDeniedHttpException('Location not allowed. You must be inside the municipality.');
        }

        return DB::transaction(function () use ($validatedData) {
            $lastNumber = $this->queueRepository->getLastTicketNumberToday($validatedData['service_id']);
            $newNumber = $lastNumber + 1;

            $ticket = $this->queueRepository->createTicket([
                'service_id' => $validatedData['service_id'],
                'user_name' => $validatedData['user_name'],
                'current_number' => $newNumber,
                'status' => 'waiting',
                'is_manual' => false,
                'latitude' => $validatedData['latitude'],
                'longitude' => $validatedData['longitude'],
                'joined_at' => now(),
            ]);

            $waitingCount = $this->queueRepository->getActiveTicketsCount($validatedData['service_id']);
            $service = $this->queueRepository->getServiceById($validatedData['service_id']);
            $estimatedWaitTime = ($waitingCount - 1) * $service->estimated_time_minutes;

            return [
                'data' => [
                    'ticket' => $ticket,
                    'display_number' => 'A' . str_pad($newNumber, 3, '0', STR_PAD_LEFT),
                    'people_ahead' => $waitingCount - 1,
                    'estimated_wait_time_minutes' => $estimatedWaitTime,
                ],
                'message' => 'Joined queue successfully',
                'code' => 201,
            ];
        });
    }
}