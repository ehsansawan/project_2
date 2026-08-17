<?php

namespace App\Http\Controllers;

use App\Http\Requests\News\GetAdminNewsRequest;
use App\Http\Requests\News\ReviewNewsRequest;
use App\Http\Requests\News\StoreNewsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\AdminNewsService;
use Illuminate\Http\JsonResponse;
use Throwable;

class AdminNewsController extends Controller
{
    private AdminNewsService $service;

    public function __construct(AdminNewsService $service)
    {
        $this->service = $service;
    }

    public function store(StoreNewsRequest $request): JsonResponse
    {
        try {
            $result = $this->service->store($request->validated(), auth('api')->id());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $this->errorCode($th));
        }
    }

    public function index(GetAdminNewsRequest $request): JsonResponse
    {
        try {
            $result = $this->service->list($request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $this->errorCode($th));
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->service->details($id);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $this->errorCode($th));
        }
    }

    public function review(ReviewNewsRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->service->review($id, $request->validated(), auth('api')->id());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $this->errorCode($th));
        }
    }

    private function errorCode(Throwable $th): int
    {
        $code = $th->getCode();
        return is_int($code) && $code >= 400 && $code <= 599 ? $code : 500;
    }
}