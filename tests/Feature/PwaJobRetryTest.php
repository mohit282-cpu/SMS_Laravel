<?php

namespace Tests\Feature;

use App\Jobs\SendWebPushNotification;
use App\Models\PushSubscription;
use App\Services\PwaService;
use App\User;
use Tests\TestCase;

class PwaJobRetryTest extends TestCase
{
    /**
     * Test job does not retry on permanent configuration failure.
     */
    public function test_job_does_not_retry_on_permanent_vapid_error()
    {
        $mockPwaService = $this->createMock(PwaService::class);
        $mockPwaService->expects($this->once())
            ->method('sendToUsers')
            ->willReturn([
                'status' => 'vapid_keys_missing',
                'message' => 'VAPID keys not configured.',
            ]);

        $job = new SendWebPushNotification([1], 'Test', 'Body');
        $result = $job->handle($mockPwaService);

        $this->assertEquals('vapid_keys_missing', $result['status']);
    }

    /**
     * Test reused device reassigns subscription ownership to new authenticated user.
     */
    public function test_reused_device_endpoint_reassigns_ownership_safely()
    {
        $user1 = User::factory()->create(['user_type' => 'teacher']);
        $user2 = User::factory()->create(['user_type' => 'teacher']);

        $endpoint = 'https://fcm.googleapis.com/fcm/send/test_reused_' . uniqid();

        // User 1 registers device
        $sub1 = PushSubscription::storeSubscription($user1->id, [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'key1_' . uniqid(),
                'auth' => 'auth1_' . uniqid(),
            ],
        ]);
        $this->assertEquals($user1->id, $sub1->user_id);

        // User 2 logs into same device and registers same endpoint
        $sub2 = PushSubscription::storeSubscription($user2->id, [
            'endpoint' => $endpoint,
            'keys' => [
                'p256dh' => 'key2_' . uniqid(),
                'auth' => 'auth2_' . uniqid(),
            ],
        ]);

        $this->assertEquals($sub1->id, $sub2->id); // Same row updated
        $this->assertEquals($user2->id, $sub2->user_id); // Reassigned to user 2
    }
}
