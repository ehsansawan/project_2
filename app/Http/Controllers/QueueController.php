<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\JoinQueueRequest;
use App\Http\Responses\ApiResponse;
use App\Services\QueueService;
use Illuminate\Http\JsonResponse;
use Throwable;

class QueueController extends Controller
{
    private QueueService $queueService;

    public function __construct(QueueService $queueService)
    {
        $this->queueService = $queueService;
    }

    public function showService(int $serviceId): JsonResponse
    {
        try {
            $result = $this->queueService->getServiceDetails($serviceId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function join(JoinQueueRequest $request): JsonResponse
    {
        try {
            $result = $this->queueService->joinQueue($request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }
}