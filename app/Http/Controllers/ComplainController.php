<?php

namespace App\Http\Controllers;

use App\Http\Requests\Complain\CreateComplainRequest;
use App\Http\Requests\Complain\GetComplainsRequest;
use App\Http\Requests\Complain\VoteRequest;
use App\Http\Requests\Shared\PaginationRequest;


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

   public function index(GetComplainsRequest $request): JsonResponse
    {
        try {
            $userId = auth('api')->id();
            $filters = $request->validated(); // تحتوي page و page_size تلقائياً
            $result = $this->complainService->getPublishedComplains($filters, $userId);

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }   

    public function myComplains(PaginationRequest $request): JsonResponse
    {
        try {
            $userId = auth('api')->id();

            // قراءة قيم الـ pagination مع حماية بسيطة
            $filters = [
                'page' => max(1, (int) $request->input('page', 1)),
                'page_size' => min(100, max(1, (int) $request->input('page_size', 15))),
            ];

            $result = $this->complainService->getUserComplains($userId, $filters);

            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function vote(VoteRequest $request): JsonResponse
    {
        try {
            $userId = auth('api')->id();
            $result = $this->complainService->vote($request->validated()['id'], $userId);
            
            return ApiResponse::success(
                $result['data'], 
                $result['message'], 
                $result['code']
            );
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function unvote(voteRequest $request): JsonResponse
    {
        try {
            $userId = auth('api')->id();
            $result = $this->complainService->unvote($request->validated()['id'], $userId);
            
            return ApiResponse::success(
                $result['data'], 
                $result['message'], 
                $result['code']
            );
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }
}