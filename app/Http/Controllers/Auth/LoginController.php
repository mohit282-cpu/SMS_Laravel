<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Maximum login attempts before temporary lockout (anti-brute-force).
     *
     * @var int
     */
    protected $maxAttempts = 5;

    /**
     * Number of minutes to throttle failed logins.
     *
     * @var int
     */
    protected $decayMinutes = 1;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    /**
     * Sanitize and validate the user login request (anti-SQL-injection and payload security).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        if ($request->has('login') && !$request->has('identity')) {
            $request->merge(['identity' => $request->input('login')]);
        }

        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $identity = trim($validated['identity']);
        $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL) !== false;
        $normPhone = \App\Helpers\Qs::normalizePhone($identity);
        $isPhone = $normPhone && \App\User::where(function($q) use ($normPhone) {
            $q->where('phone', $normPhone)->orWhere('phone2', $normPhone);
        })->exists();

        $field = $isEmail ? 'email' : ($isPhone ? 'phone' : 'username');
        $val = $isPhone ? $normPhone : $identity;

        $request->merge([
            'identity' => $identity,
            $field => $val,
        ]);
    }

    /**
     * Get the needed authorization credentials from the request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        $rawIdentity = trim((string) ($request->input('identity') ?: $request->input('login', '')));
        $rawIdentity = str_replace(["\0", "\x00"], '', $rawIdentity);
        $password = (string) $request->input('password', '');

        if (filter_var($rawIdentity, FILTER_VALIDATE_EMAIL)) {
            return ['email' => $rawIdentity, 'password' => $password];
        }

        $normPhone = \App\Helpers\Qs::normalizePhone($rawIdentity);
        if ($normPhone && \App\User::where(function($q) use ($normPhone) {
            $q->where('phone', $normPhone)->orWhere('phone2', $normPhone);
        })->exists()) {
            return ['phone' => $normPhone, 'password' => $password];
        }

        return [
            'username' => $rawIdentity,
            'password' => $password,
        ];
    }

    /**
     * Check if the authenticated user account is active.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return mixed
     */
    protected function authenticated(Request $request, $user)
    {
        if (isset($user->is_active) && !$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'identity' => __('Your account has been deactivated or requires activation. Please contact the administration.'),
            ]);
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Login field identifier (username or email).
     *
     * @return string
     */
    public function username()
    {
        $identity = trim((string) request()->input('identity', ''));
        if (filter_var($identity, FILTER_VALIDATE_EMAIL)) {
            return 'email';
        }
        $normPhone = \App\Helpers\Qs::normalizePhone($identity);
        if ($normPhone && \App\User::where(function($q) use ($normPhone) {
            $q->where('phone', $normPhone)->orWhere('phone2', $normPhone);
        })->exists()) {
            return 'phone';
        }
        return 'username';
    }

    /**
     * Get the throttle key for the given request (IP-bound anti-brute-force key).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function throttleKey(Request $request)
    {
        $identity = Str::lower((string) $request->input('identity', ''));
        return Str::transliterate($identity . '|' . $request->ip());
    }
}
