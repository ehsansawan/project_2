<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Complain\GetAdminComplainsRequest;
use App\Http\Requests\Complain\ReviewComplainRequest;
use App\Http\Requests\Complain\UpdateComplainStatusRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminComplainService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminComplainController extends Controller
{
    private AdminComplainService $service;

    public function __construct(AdminComplainService $service)
    {
        $this->service = $service;
    }

    public function index(GetAdminComplainsRequest $request): JsonResponse
    {
        try {
            $result = $this->service->list($request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(),500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->service->details($id);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(),500);
        }
    }

    public function review(ReviewComplainRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->review($id, $request->validated(), auth('api')->id());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(),500);
        }
    }

    public function updateStatus(UpdateComplainStatusRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->updateStatus($id, $request->validated(), auth('api')->id());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(),500);
        }
    }
}