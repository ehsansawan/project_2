<?php

namespace App\Http\Controllers;

use App\Http\Requests\News\GetNewsRequest;
use App\Http\Responses\ApiResponse;
use App\Services\NewsService;
use Illuminate\Http\JsonResponse;
use Throwable;

class NewsController extends Controller
{
    private NewsService $service;

    public function __construct(NewsService $service)
    {
        $this->service = $service;
    }

    public function index(GetNewsRequest $request): JsonResponse
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

    private function errorCode(Throwable $th): int
    {
        $code = $th->getCode();
        return is_int($code) && $code >= 400 && $code <= 599 ? $code : 500;
    }
}