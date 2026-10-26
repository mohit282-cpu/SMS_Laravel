<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class SetLocale
{
    /**
     * Supported application locales.
     *
     * @var array
     */
    protected $supportedLocales = ['ne', 'en'];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $locale = null;

        // 1. Priority: Session
        if ($request->hasSession() && $request->session()->has('locale')) {
            $sessionLocale = $request->session()->get('locale');
            if (in_array($sessionLocale, $this->supportedLocales, true)) {
                $locale = $sessionLocale;
            }
        }

        // 2. Authenticated user's preference
        if (!$locale && Auth::check() && !empty(Auth::user()->locale)) {
            $userLocale = Auth::user()->locale;
            if (in_array($userLocale, $this->supportedLocales, true)) {
                $locale = $userLocale;
                if ($request->hasSession()) {
                    $request->session()->put('locale', $locale);
                }
            }
        }

        // 3. Default configured locale (English 'en')
        if (!$locale) {
            $locale = config('app.locale', 'en');
            if ($request->hasSession()) {
                $request->session()->put('locale', $locale);
            }
        }

        App::setLocale($locale);

        return $next($request);
    }
}
