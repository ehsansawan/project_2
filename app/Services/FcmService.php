<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

class FcmService
{
    public function __construct(private Messaging $messaging)
    {
    }

    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $token = $user->fcm_token;

        if (!$token) {
            Log::warning('No FCM token for user', ['user_id' => $user->id]);
            return;
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData(array_merge($data, ['user_id' => (string) $user->id]));

            $this->messaging->send($message);
        } catch (\Throwable $e) {
            Log::error('FCM send failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            // token تالف → امسحه
            $user->update(['fcm_token' => null]);
        }
    }

    /**
     * إرسال FCM لجهاز محدد عبر token (بدون حفظ)
     * ترجع true = نجح، false = فشل
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        if (!$token) {
            Log::warning('Empty FCM token');
            return false;
        }

        try {
            $message = CloudMessage::withTarget('token', $token)
                ->withNotification(FcmNotification::create($title, $body))
                ->withData($data);

            $this->messaging->send($message);

            return true;

        } catch (\Throwable $e) {
            Log::error('FCM send failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * حفظ الإشعار في جدول notifications (بدون إرسال)
     */
    public function storeNotification(User $user, string $title, string $body, array $data = []): void
    {
        Notification::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }
}
