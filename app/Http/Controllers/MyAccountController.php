<?php

namespace App\Http\Controllers;


use App\Helpers\Qs;
use App\Http\Requests\UserChangePass;
use App\Http\Requests\UserUpdate;
use App\Repositories\UserRepo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class MyAccountController extends Controller
{
    protected $user;

    public function __construct(UserRepo $user)
    {
        $this->user = $user;
    }

    public function edit_profile()
    {
        $d['my'] = Auth::user();
        return view('pages.support_team.my_account', $d);
    }

    public function update_profile(UserUpdate $req)
    {
        $user = Auth::user();
        if (!$user) {
            abort(401);
        }

        $fields = ['email', 'phone', 'phone2', 'address', 'locale', 'calendar_display'];
        if (!$user->username) {
            $fields[] = 'username';
        }
        $d = $req->only($fields);

        if ($req->filled('locale') && in_array($req->locale, ['ne', 'en'], true)) {
            session()->put('locale', $req->locale);
            app()->setLocale($req->locale);
        }

        if ($req->filled('calendar_display') && in_array($req->calendar_display, ['default', 'bs', 'ad', 'both'], true)) {
            if ($req->calendar_display === 'default') {
                session()->forget('calendar_display');
            } else {
                session()->put('calendar_display', $req->calendar_display);
            }
        }

        if (!$user->username && !$req->username && !$req->email) {
            return back()->with('pop_error', __('msg.user_invalid'));
        }

        $user_type = $user->user_type;
        $code = $user->code ?: strtoupper(\Illuminate\Support\Str::random(10));

        if ($req->hasFile('photo')) {
            $photo = $req->file('photo');
            $f = Qs::getFileMetaData($photo);
            $f['name'] = 'photo.' . $f['ext'];
            $f['path'] = $photo->storeAs(Qs::getUploadPath($user_type) . $code, $f['name']);
            $d['photo'] = asset('storage/' . $f['path']);
        }

        $this->user->update($user->id, $d);
        return back()->with('flash_success', __('msg.update_ok'));
    }

    public function change_pass(UserChangePass $req)
    {
        $user_id = Auth::user()->id;
        $my_pass = Auth::user()->password;
        $old_pass = $req->current_password;
        $new_pass = $req->password;

        if(password_verify($old_pass, $my_pass)){
            $data['password'] = Hash::make($new_pass);
            $this->user->update($user_id, $data);

            app(\App\Services\AuditLogService::class)->log(
                'auth.password_change',
                'security',
                "User '" . Auth::user()->name . "' changed their account password.",
                Auth::user(),
                [],
                ['changed_at' => now()->toIso8601String()],
                'success',
                Auth::user()
            );

            return back()->with('flash_success', __('msg.p_reset'));
        }

        app(\App\Services\AuditLogService::class)->log(
            'auth.password_change_failed',
            'security',
            "User '" . Auth::user()->name . "' attempted to change password with invalid current credentials.",
            Auth::user(),
            [],
            ['failed_at' => now()->toIso8601String()],
            'failure',
            Auth::user()
        );

        return back()->with('flash_danger', __('msg.p_reset_fail'));
    }

}
