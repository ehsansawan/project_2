<?php

namespace Tests\Feature;

use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    public function test_user_can_list_their_own_notifications_only(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['notification.index']);

        Notification::create(['user_id' => $user->id, 'title' => 'Mine A', 'body' => 'x']);
        Notification::create(['user_id' => $user->id, 'title' => 'Mine B', 'body' => 'x']);

        $otherUser = $this->makeUser();
        Notification::create(['user_id' => $otherUser->id, 'title' => 'Not mine', 'body' => 'x']);

        $response = $this->getJson('/api/notification', $headers);

        $response->assertStatus(200);
        $titles = collect($response->json('data.data'))->pluck('title')->all();
        $this->assertCount(2, $titles);
        $this->assertContains('Mine A', $titles);
        $this->assertContains('Mine B', $titles);
        $this->assertNotContains('Not mine', $titles);
    }

    public function test_notifications_can_be_filtered_by_read_status(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['notification.index']);

        Notification::create(['user_id' => $user->id, 'title' => 'Unread', 'body' => 'x', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'title' => 'Read', 'body' => 'x', 'is_read' => true]);

        $response = $this->getJson('/api/notification?is_read=0', $headers);

        $response->assertStatus(200);
        $titles = collect($response->json('data.data'))->pluck('title')->all();
        $this->assertSame(['Unread'], $titles);
    }

    public function test_user_can_mark_their_own_notification_as_read(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['notification.markAsRead']);

        $notification = Notification::create(['user_id' => $user->id, 'title' => 'x', 'body' => 'x', 'is_read' => false]);

        $response = $this->postJson("/api/notification/{$notification->id}/read", [], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => 1]);
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $owner = $this->makeUser();
        $notification = Notification::create(['user_id' => $owner->id, 'title' => 'x', 'body' => 'x']);

        $intruder = $this->makeUser();
        [, $headers] = $this->actingAsApi($intruder, ['notification.markAsRead']);

        $response = $this->postJson("/api/notification/{$notification->id}/read", [], $headers);

        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id, 'is_read' => 0]);
    }

    public function test_user_can_delete_their_own_notification(): void
    {
        $user = $this->makeUser();
        [, $headers] = $this->actingAsApi($user, ['notification.destroy']);

        $notification = Notification::create(['user_id' => $user->id, 'title' => 'x', 'body' => 'x']);

        $response = $this->deleteJson("/api/notification/{$notification->id}", [], $headers);

        $response->assertStatus(200);
        $this->assertDatabaseMissing('notifications', ['id' => $notification->id]);
    }

    public function test_user_cannot_delete_another_users_notification(): void
    {
        $owner = $this->makeUser();
        $notification = Notification::create(['user_id' => $owner->id, 'title' => 'x', 'body' => 'x']);

        $intruder = $this->makeUser();
        [, $headers] = $this->actingAsApi($intruder, ['notification.destroy']);

        $response = $this->deleteJson("/api/notification/{$notification->id}", [], $headers);

        $response->assertStatus(404);
        $this->assertDatabaseHas('notifications', ['id' => $notification->id]);
    }
}
