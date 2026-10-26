<?php

namespace App\Models;

use App\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    /**
     * The attributes that should be hidden for serialization to prevent secret exposure.
     */
    protected $hidden = [
        'auth_token',
        'public_key',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Find subscription by raw endpoint URL
     */
    public static function findByEndpoint(string $endpoint): ?self
    {
        return self::where('endpoint_hash', hash('sha256', $endpoint))->first();
    }

    /**
     * Helper to store or update subscription by hash of endpoint safely.
     * Prevents orphan records, eliminates race conditions with database transactions,
     * and safely reassigns ownership if a shared device changes authenticated users.
     */
    public static function storeSubscription(int $userId, array $data): self
    {
        $endpoint = $data['endpoint'];
        $hash = hash('sha256', $endpoint);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($userId, $endpoint, $hash, $data) {
            $sub = self::where('endpoint_hash', $hash)->lockForUpdate()->first();

            $pubKey = $data['keys']['p256dh'] ?? $data['public_key'] ?? null;
            $authToken = $data['keys']['auth'] ?? $data['auth_token'] ?? null;
            $contentEncoding = $data['content_encoding'] ?? 'aesgcm';

            if ($sub) {
                if ($sub->user_id !== $userId) {
                    \Illuminate\Support\Facades\Log::info('Push subscription endpoint ownership updated for reused device.', [
                        'previous_user_id' => $sub->user_id,
                        'new_user_id' => $userId,
                        'endpoint_hash_prefix' => substr($hash, 0, 8),
                    ]);
                }

                $sub->update([
                    'user_id' => $userId,
                    'endpoint' => $endpoint,
                    'public_key' => $pubKey ?: $sub->public_key,
                    'auth_token' => $authToken ?: $sub->auth_token,
                    'content_encoding' => $contentEncoding ?: $sub->content_encoding,
                ]);

                return $sub;
            }

            return self::create([
                'endpoint_hash' => $hash,
                'user_id' => $userId,
                'endpoint' => $endpoint,
                'public_key' => $pubKey,
                'auth_token' => $authToken,
                'content_encoding' => $contentEncoding,
            ]);
        });
    }
}
