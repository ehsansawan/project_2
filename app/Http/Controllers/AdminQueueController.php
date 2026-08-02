<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\AddManualVisitorRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminQueueService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminQueueController extends Controller
{
    private AdminQueueService $adminQueueService;

    public function __construct(AdminQueueService $adminQueueService)
    {
        $this->adminQueueService = $adminQueueService;
    }

    public function dashboard(int $serviceId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->getDashboard($serviceId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function addManual(AddManualVisitorRequest $request, int $serviceId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->addManualVisitor($serviceId, $employeeId, $request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function callNext(int $serviceId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->callNext($serviceId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function markAsServed(int $ticketId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->markAsServed($ticketId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function markAsNoShow(int $ticketId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->markAsNoShow($ticketId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function returnToQueue(int $ticketId): JsonResponse
    {
        try {
            $employeeId = auth('api')->id();
            $result = $this->adminQueueService->returnToQueue($ticketId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }
}