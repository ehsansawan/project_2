<?php

namespace App\Http\Controllers;

use App\Http\Requests\Report\CreateReportRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ReportController extends Controller
{
    private ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function store(CreateReportRequest $request)
    {
        $data = [];
        
        try {
            $userId = auth('api')->id();
            $result = $this->reportService->createReport($request->validated(), $userId);

          return ApiResponse::success($result['data'], $result['message'], $result['code']);

        }catch(Throwable $th){
            return ApiResponse::error($data, $th->getMessage(), $th->getCode() ?: 500);
        }
    }
}

