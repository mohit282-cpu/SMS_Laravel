<?php

namespace App\Jobs;

use App\Services\PwaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendWebPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Bounded retry parameters
     */
    public $tries = 3;
    public $backoff = [10, 30, 60];

    protected $userIds;
    protected $title;
    protected $body;
    protected $targetUrl;

    /**
     * Create a new job instance.
     *
     * @param int|array $userIds
     * @param string $title
     * @param string $body
     * @param string|null $targetUrl
     */
    public function __construct($userIds, string $title, string $body, ?string $targetUrl = null)
    {
        $this->userIds = is_array($userIds) ? $userIds : [$userIds];
        $this->title = $title;
        $this->body = $body;
        $this->targetUrl = $targetUrl;
    }

    /**
     * Execute the job.
     *
     * @param PwaService $pwaService
     * @return array
     */
    public function handle(PwaService $pwaService)
    {
        $result = $pwaService->sendToUsers($this->userIds, $this->title, $this->body, $this->targetUrl);

        // Permanent outcomes that must never trigger retries:
        $permanentStatuses = [
            'success',
            'no_recipients',
            'no_subscriptions',
            'vapid_keys_missing',
            'vapid_keys_invalid',
            'dependency_missing',
            'invalid_role',
            'partial', // Never retry partial deliveries to avoid sending duplicate notifications
        ];

        if (in_array($result['status'], $permanentStatuses, true)) {
            if ($result['status'] !== 'success' && $result['status'] !== 'partial') {
                Log::info('SendWebPushNotification stopped: permanent non-retryable status (' . $result['status'] . ').');
            }
            return $result;
        }

        // Retryable failure (e.g. provider network timeout or transient error):
        if ($result['status'] === 'failed') {
            if ($this->attempts() < $this->tries) {
                $seconds = $this->backoff[$this->attempts() - 1] ?? 30;
                $this->release($seconds);
                return $result;
            }
        }

        return $result;
    }

    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        $correlationId = (string) \Illuminate\Support\Str::uuid();
        try {
            Log::warning('SendWebPushNotification job failed permanently', [
                'correlation_id' => $correlationId,
                'category' => 'JOB_FAILED_PERMANENTLY',
                'recipients_count' => count($this->userIds),
            ]);
        } catch (\Throwable $logEx) {
            // Silently swallow logging failure to prevent unexpected worker crash
        }
    }
}
