<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    public function index($request): array
    {
        $user = auth('api')->user();

        $query = Notification::query()->where('user_id', $user->id);

        if (array_key_exists('is_read', $request) && $request['is_read'] !== null) {
            $query->where('is_read', filter_var($request['is_read'], FILTER_VALIDATE_BOOLEAN));
        }

        $notifications = $query->latest()->paginate(15);

        return ['data' => $notifications, 'message' => 'notifications retrieved successfully', 'code' => 200];
    }

    public function markAsRead($request): array
    {
        $user = auth('api')->user();
        $notification = Notification::query()->where('user_id', $user->id)->find($request['id']);

        if (!$notification) {
            return ['data' => null, 'message' => 'notification not found', 'code' => 404];
        }

        if (!$notification->is_read) {
            $notification->update(['is_read' => true]);
        }

        return ['data' => $notification, 'message' => 'notification marked as read', 'code' => 200];
    }

    public function destroy($request): array
    {
        $user = auth('api')->user();
        $notification = Notification::query()->where('user_id', $user->id)->find($request['id']);

        if (!$notification) {
            return ['data' => null, 'message' => 'notification not found', 'code' => 404];
        }

        $notification->delete();

        return ['data' => null, 'message' => 'notification deleted successfully', 'code' => 200];
    }
}
