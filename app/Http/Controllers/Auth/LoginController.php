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
        // 1. Sanitize identity input: strip null bytes, newlines, and normalize whitespace
        $rawIdentity = (string) $request->input('identity', '');
        $identity = trim(str_replace(["\0", "\x00", "\r", "\n"], '', $rawIdentity));

        // 2. Strict validation rules against SQL injection patterns and buffer overflows
        $rules = [
            'identity' => [
                'required',
                'string',
                'max:100',
                function ($attribute, $value, $fail) {
                    // Block typical SQL injection vectors: quote escaping, comment sequences, union/sleep/benchmark exploits
                    if (preg_match('/[\'\"\\\;]|(--)|(\/\*)|(\*\/)|(\b(union|select|insert|update|delete|drop|alter|truncate|exec|benchmark|sleep)\b)/i', $value)) {
                        $fail('Invalid login credential characters detected.');
                    }
                },
            ],
            'password' => ['required', 'string', 'max:255'],
        ];

        $request->validate($rules);

        // Merge sanitized identity into request
        $isEmail = filter_var($identity, FILTER_VALIDATE_EMAIL);
        $field = $isEmail ? 'email' : 'username';
        $request->merge([
            'identity' => $identity,
            $field => $identity,
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
        $field = $this->username();
        $identity = trim(str_replace(["\0", "\x00"], '', (string) $request->input($field, '')));

        return [
            $field => $identity,
            'password' => (string) $request->input('password', ''),
        ];
    }

    /**
     * Login field identifier (username or email).
     *
     * @return string
     */
    public function username()
    {
        $identity = trim((string) request()->input('identity', ''));
        return filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
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
