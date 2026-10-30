<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\StudentRecord;
use App\Services\AuditLogService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ParentActivationController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->middleware('guest');
        $this->middleware('throttle:6,1')->only('activate');
        $this->auditLog = $auditLog;
    }

    /**
     * Display the secure parent account activation form.
     *
     * @param string $token
     * @return \Illuminate\View\View
     */
    public function showActivateForm($token)
    {
        $cleanToken = trim($token);

        $parent = User::where('activation_token', $cleanToken)
            ->where('user_type', 'parent')
            ->first();

        if (!$parent) {
            return view('pages.parent.activate_error', [
                'message' => __('This activation link is invalid or has already been used.'),
            ]);
        }

        if ($parent->activation_token_expires_at && $parent->activation_token_expires_at->isPast()) {
            return view('pages.parent.activate_error', [
                'message' => __('This activation link has expired. Please contact the school administration to request a new link.'),
            ]);
        }

        // Retrieve linked children for confirmation display
        $children = StudentRecord::where('my_parent_id', $parent->id)->with(['user', 'my_class'])->get();

        return view('pages.parent.activate', [
            'parent' => $parent,
            'token' => $cleanToken,
            'children' => $children,
        ]);
    }

    /**
     * Process parent account activation and password setup.
     *
     * @param Request $request
     * @param string $token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function activate(Request $request, $token)
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.min' => __('Password must be at least 8 characters long.'),
            'password.confirmed' => __('Password confirmation does not match.'),
        ]);

        $cleanToken = trim($token);

        $parent = User::where('activation_token', $cleanToken)
            ->where('user_type', 'parent')
            ->first();

        if (!$parent) {
            return redirect()->route('login')->with('flash_danger', __('The activation token is invalid or has already been consumed.'));
        }

        if ($parent->activation_token_expires_at && $parent->activation_token_expires_at->isPast()) {
            return redirect()->route('login')->with('flash_danger', __('This activation link has expired. Please request a new activation link from the school administration.'));
        }

        // Atomically set password, activate account, and revoke the single-use token
        $parent->update([
            'password' => Hash::make($request->password),
            'activation_token' => null,
            'activation_token_expires_at' => null,
            'is_active' => true,
            'email_verified_at' => $parent->email_verified_at ?: now(),
        ]);

        $this->auditLog->log(
            'parent.activated',
            'security',
            "Parent account '{$parent->name}' (ID: {$parent->id}) activated successfully.",
            $parent,
            [],
            ['parent_id' => $parent->id, 'phone' => $parent->phone, 'email' => $parent->email],
            'success',
            $parent
        );

        return redirect()->route('login')->with('flash_success', __('Your parent account has been activated successfully! You may now sign in using your mobile number, email, or username.'));
    }
}
