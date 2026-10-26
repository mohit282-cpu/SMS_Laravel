<?php

namespace App\Http\Middleware\Custom;

use Closure;
use App\Helpers\Qs;
use Illuminate\Support\Facades\Auth;

class TeamLibrary
{
    /**
     * Handle an incoming request.
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

        if (!Qs::userIsTeamLibrary()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['message' => __('msg.denied')], 403);
            }
            return redirect()->route('dashboard')->with('pop_error', __('msg.denied'));
        }

        return $next($request);
    }
}
