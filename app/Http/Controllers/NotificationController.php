<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Throwable;

class NotificationController extends Controller
{
    private NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function index(Request $request)
    {
        $data = [];
        try {
            $data = $this->notificationService->index(['is_read' => $request->query('is_read')]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function markAsRead($id)
    {
        $data = [];
        try {
            $data = $this->notificationService->markAsRead(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }

    public function destroy($id)
    {
        $data = [];
        try {
            $data = $this->notificationService->destroy(['id' => $id]);
            return ApiResponse::success($data['data'], $data['message'], $data['code']);
        } catch (Throwable $th) {
            $message = $th->getMessage();
            return ApiResponse::error($data, $message);
        }
    }
}
