<?php

namespace App\Http\Middleware\Custom;

use Closure;
use App\Helpers\Qs;
use Illuminate\Support\Facades\Auth;

class SuperAdmin
{
    /**
     * Handle an incoming request.
     * Enforce strict Super Admin authorization with HTTP 403 on forbidden access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        if (!Qs::userIsSuperAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('msg.denied') ?: 'Unauthorized action. Super Admin required.'], 403);
            }
            abort(403, __('msg.denied') ?: 'Unauthorized action. Super Admin privilege required.');
        }

        return $next($request);
    }
}
