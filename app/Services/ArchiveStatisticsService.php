<?php

namespace App\Services;

use App\Repositories\ArchiveStatisticsRepository;

class ArchiveStatisticsService
{
    private ArchiveStatisticsRepository $repository;

    public function __construct(ArchiveStatisticsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getOverview(string $period = 'today'): array
    {
        $summary = $this->repository->getOverview($period);
        $byService = $this->repository->getStatsByService($period);

        return [
            'data' => [
                'period' => $period,
                'summary' => $summary,
                'by_service' => $byService,
            ],
            'message' => 'Statistics retrieved successfully',
            'code' => 200,
        ];
    }

    public function getServiceStats(int $serviceId, string $period = 'today'): array
    {
        $stats = $this->repository->getStatsByService($period);
        $serviceStats = collect($stats)->firstWhere('service_id', $serviceId);

        return [
            'data' => $serviceStats ?? [],
            'message' => 'Service statistics retrieved successfully',
            'code' => 200,
        ];
    }

    public function getEmployeeStats(string $period = 'today'): array
    {
        $stats = $this->repository->getEmployeeStats($period);

        return [
            'data' => ['employees' => $stats],
            'message' => 'Employee statistics retrieved successfully',
            'code' => 200,
        ];
    }

    public function getArchiveHistory(array $filters, int $perPage = 20): array
    {
        $history = $this->repository->getArchiveHistory($filters, $perPage);

        return [
            'data' => $history,
            'message' => 'Archive history retrieved successfully',
            'code' => 200,
        ];
    }
}