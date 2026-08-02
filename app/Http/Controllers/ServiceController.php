<?php

namespace App\Http\Controllers;

use App\Http\Requests\Service\CreateServiceRequest;
use App\Http\Requests\Service\UpdateServiceRequest;
use App\Http\Requests\Service\AssignEmployeeRequest;
use App\Http\Responses\ApiResponse;
use App\Services\ServiceService;
use Illuminate\Http\JsonResponse;
use Throwable;

class ServiceController extends Controller
{
    private ServiceService $serviceService;

    public function __construct(ServiceService $serviceService)
    {
        $this->serviceService = $serviceService;
    }

    public function index(): JsonResponse
    {
        try {
            $result = $this->serviceService->getAllServices();
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $result = $this->serviceService->getServiceById($id);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function store(CreateServiceRequest $request): JsonResponse
    {
        try {
            $result = $this->serviceService->createService($request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), 500);
        }
    }

    public function update(UpdateServiceRequest $request, int $id): JsonResponse
    {
        try {
            $result = $this->serviceService->updateService($id, $request->validated());
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->serviceService->deleteService($id);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function assignEmployee(AssignEmployeeRequest $request, int $serviceId): JsonResponse
    {
        try {
            $result = $this->serviceService->assignEmployee($serviceId, $request->employee_id);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function unassignEmployee(int $serviceId, int $employeeId): JsonResponse
    {
        try {
            $result = $this->serviceService->unassignEmployee($serviceId, $employeeId);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }

    public function getQueueInfo(string $qrCodeString): JsonResponse
    {
        try {
            $result = $this->serviceService->getServiceByQrCode($qrCodeString);
            return ApiResponse::success($result['data'], $result['message'], $result['code']);
        } catch (Throwable $th) {
            return ApiResponse::error([], $th->getMessage(), $th->getCode() ?: 500);
        }
    }
}