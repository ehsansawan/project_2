<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complain\CreateComplainRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ComplainService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ComplainController extends Controller
{
    private ComplainService $complainService;

    public function __construct(ComplainService $complainService)
    {
        $this->complainService = $complainService;
    }

    public function store(CreateComplainRequest $request): JsonResponse
    {
        $data = [];
        try {
            $userId = auth('api')->id();
            $result = $this->complainService->createComplain($request->validated(), $userId);
            
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error($data, $th->getMessage(), 500);
        }
    }
}