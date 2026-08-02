<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\ArchiveStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ArchiveStatisticsController extends Controller
{
    private ArchiveStatisticsService $service;

    public function __construct(ArchiveStatisticsService $service)
    {
        $this->service = $service;
    }

    public function overview(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'today');
            $result = $this->service->getOverview($period);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }

    public function serviceStats(int $serviceId, Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'today');
            $result = $this->service->getServiceStats($serviceId, $period);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }

    public function employeeStats(Request $request): JsonResponse
    {
        try {
            $period = $request->query('period', 'today');
            $result = $this->service->getEmployeeStats($period);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }

    public function history(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['date', 'service_id', 'status', 'archival_reason']);
            $perPage = $request->query('per_page', 20);
            $result = $this->service->getArchiveHistory($filters, $perPage);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }
}