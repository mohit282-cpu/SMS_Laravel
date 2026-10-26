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

        // Terminate immediately without retrying on permanent server configuration errors
        if (in_array($result['status'], ['vapid_keys_missing', 'vapid_keys_invalid', 'dependency_missing'], true)) {
            Log::info('SendWebPushNotification stopped: permanent configuration status encountered (' . $result['status'] . '). Retries cancelled.');
            return $result;
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
