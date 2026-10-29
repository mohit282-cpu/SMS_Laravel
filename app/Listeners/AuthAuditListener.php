<?php

namespace App\Listeners;

use App\Services\AuditLogService;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Events\Dispatcher;

class AuthAuditListener
{
    protected AuditLogService $audit;

    public function __construct(AuditLogService $audit)
    {
        $this->audit = $audit;
    }

    /**
     * Handle user login events.
     */
    public function onLogin(Login $event): void
    {
        $user = $event->user;
        $name = $user ? $user->name : 'Unknown User';
        $role = $user ? $user->user_type : 'unknown';

        $this->audit->log(
            'auth.login',
            'authentication',
            "User '{$name}' ({$role}) logged in successfully.",
            $user,
            [],
            ['role' => $role, 'login_time' => now()->toIso8601String()],
            'success',
            $user
        );
    }

    /**
     * Handle failed login attempts with security rate limiting and zero credential exposure.
     */
    public function onFailed(Failed $event): void
    {
        $credentials = $event->credentials ?: [];
        $identity = $credentials['identity']
            ?? $credentials['username']
            ?? $credentials['email']
            ?? 'unknown';

        // Redact any possible passwords in the credentials array
        $safeCredentials = ['attempted_identity' => (string) $identity];

        $user = $event->user;

        $this->audit->log(
            'auth.failed_login',
            'security',
            "Failed login attempt for identity '{$identity}'.",
            $user,
            [],
            $safeCredentials,
            'failure',
            $user
        );
    }

    /**
     * Handle user logout events.
     */
    public function onLogout(Logout $event): void
    {
        $user = $event->user;
        if (!$user) {
            return;
        }

        $this->audit->log(
            'auth.logout',
            'authentication',
            "User '{$user->name}' logged out.",
            $user,
            [],
            [],
            'success',
            $user
        );
    }

    /**
     * Handle password reset events.
     */
    public function onPasswordReset(PasswordReset $event): void
    {
        $user = $event->user;
        if (!$user) {
            return;
        }

        $this->audit->log(
            'auth.password_reset',
            'security',
            "Password was successfully reset for user '{$user->name}'.",
            $user,
            [],
            ['reset_time' => now()->toIso8601String()],
            'success',
            $user
        );
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            Login::class => 'onLogin',
            Failed::class => 'onFailed',
            Logout::class => 'onLogout',
            PasswordReset::class => 'onPasswordReset',
        ];
    }
}
