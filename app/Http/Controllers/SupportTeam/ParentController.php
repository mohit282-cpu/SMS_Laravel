<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\StudentRecord;
use App\Services\AuditLogService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ParentController extends Controller
{
    protected $auditLog;

    public function __construct(AuditLogService $auditLog)
    {
        $this->middleware('teamSA');
        $this->auditLog = $auditLog;
    }

    /**
     * Display a listing of all registered parents with linked students.
     */
    public function index(Request $request)
    {
        $query = User::where('user_type', 'parent')->with(['children.user', 'children.my_class', 'children.section']);

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%");
            });
        }

        $data['parents'] = $query->orderBy('name')->paginate(25);
        $data['unlinked_students'] = StudentRecord::whereNull('my_parent_id')->with(['user', 'my_class'])->take(50)->get();

        return view('pages.support_team.parents.index', $data);
    }

    /**
     * Manually create a new parent account from the administrator interface.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|min:6|max:20',
            'email' => 'nullable|email|max:100|unique:users,email',
            'address' => 'nullable|string|max:255',
            'student_id' => 'nullable|exists:student_records,id',
            'relationship' => 'nullable|string|in:Father,Mother,Guardian,Other',
        ]);

        $normPhone = Qs::normalizePhone($request->phone);

        // Duplicate check
        $exists = User::where('user_type', 'parent')
            ->where(function ($q) use ($normPhone) {
                $q->where('phone', $normPhone)->orWhere('phone2', $normPhone);
            })->exists();

        if ($exists) {
            return back()->withInput()->with('flash_danger', __('A parent account with this mobile number already exists.'));
        }

        $tempPassword = Str::random(32);
        $activationToken = Str::random(64);
        $baseUsername = $normPhone ? 'p_' . $normPhone : 'p_' . Str::slug($request->name, '_');
        $username = $baseUsername;
        $counter = 1;
        while (User::where('username', $username)->exists()) {
            $username = $baseUsername . '_' . $counter++;
        }

        $parent = DB::transaction(function () use ($request, $normPhone, $username, $tempPassword, $activationToken) {
            $p = User::create([
                'name' => ucwords(trim($request->name)),
                'phone' => $normPhone,
                'email' => $request->filled('email') ? strtolower(trim($request->email)) : null,
                'user_type' => 'parent',
                'username' => $username,
                'password' => Hash::make($tempPassword),
                'code' => strtoupper(Str::random(10)),
                'photo' => Qs::getDefaultUserImage(),
                'address' => $request->address,
                'activation_token' => $activationToken,
                'activation_token_expires_at' => now()->addDays(7),
                'is_active' => true,
            ]);

            if ($request->filled('student_id')) {
                StudentRecord::where('id', $request->student_id)->update([
                    'my_parent_id' => $p->id,
                    'parent_relationship' => $request->relationship ?: 'Guardian',
                ]);
            }

            return $p;
        });

        $this->auditLog->log(
            'parent.manual_create',
            'administration',
            "Administrator created parent account '{$parent->name}' (Phone: {$parent->phone})",
            $parent,
            [],
            ['parent_id' => $parent->id, 'phone' => $parent->phone],
            'success'
        );

        $activationUrl = route('parent.activate', $activationToken);
        return back()->with('flash_success', __('Parent account created successfully.'))
            ->with('activation_url', $activationUrl)
            ->with('parent_name', $parent->name);
    }

    /**
     * Show parent details and linked students.
     */
    public function show($id)
    {
        $id = Qs::decodeHash($id) ?: (is_numeric($id) ? (int)$id : null);
        $parent = User::where('id', $id)->where('user_type', 'parent')->with(['children.user', 'children.my_class', 'children.section'])->firstOrFail();

        $data['parent'] = $parent;
        $data['all_students'] = StudentRecord::with(['user', 'my_class'])->orderBy('id', 'desc')->take(200)->get();

        return view('pages.support_team.parents.show', $data);
    }

    /**
     * Update parent contact details.
     */
    public function update(Request $request, $id)
    {
        $id = Qs::decodeHash($id) ?: (is_numeric($id) ? (int)$id : null);
        $parent = User::where('id', $id)->where('user_type', 'parent')->firstOrFail();

        $request->validate([
            'name' => 'required|string|min:3|max:100',
            'phone' => 'required|string|min:6|max:20',
            'email' => ['nullable', 'email', 'max:100', Rule::unique('users', 'email')->ignore($parent->id)],
            'address' => 'nullable|string|max:255',
        ]);

        $normPhone = Qs::normalizePhone($request->phone);

        // Duplicate check against other parents
        $conflict = User::where('user_type', 'parent')
            ->where('id', '!=', $parent->id)
            ->where(function ($q) use ($normPhone) {
                $q->where('phone', $normPhone)->orWhere('phone2', $normPhone);
            })->exists();

        if ($conflict) {
            return back()->withInput()->with('flash_danger', __('Another parent account already uses this mobile number.'));
        }

        $oldData = $parent->only(['name', 'phone', 'email', 'address']);

        $parent->update([
            'name' => ucwords(trim($request->name)),
            'phone' => $normPhone,
            'email' => $request->filled('email') ? strtolower(trim($request->email)) : null,
            'address' => $request->address,
        ]);

        $this->auditLog->log(
            'parent.update',
            'administration',
            "Administrator updated profile for parent '{$parent->name}'",
            $parent,
            $oldData,
            $parent->only(['name', 'phone', 'email', 'address']),
            'success'
        );

        return back()->with('flash_success', __('Parent details updated successfully.'));
    }

    /**
     * Link an existing student to this parent.
     */
    public function link_student(Request $request, $parent_id)
    {
        $parent_id = Qs::decodeHash($parent_id) ?: (is_numeric($parent_id) ? (int)$parent_id : null);
        $parent = User::where('id', $parent_id)->where('user_type', 'parent')->firstOrFail();

        $request->validate([
            'student_id' => 'required',
            'relationship' => 'nullable|string|in:Father,Mother,Guardian,Other',
        ]);

        $stId = $request->student_id;
        $sr = StudentRecord::with('user')
            ->where('id', $stId)
            ->orWhere('user_id', $stId)
            ->firstOrFail();

        $sr->update([
            'my_parent_id' => $parent->id,
            'parent_relationship' => $request->relationship ?: 'Guardian',
        ]);

        $this->auditLog->log(
            'parent.link_student',
            'students',
            "Linked student '{$sr->user->name}' to parent '{$parent->name}'",
            $sr->user,
            [],
            ['student_id' => $sr->user_id, 'parent_id' => $parent->id, 'relationship' => $sr->parent_relationship],
            'success'
        );

        return back()->with('flash_success', __('Student successfully linked to parent.'));
    }

    /**
     * Unlink a student from this parent.
     */
    public function unlink_student($parent_id, $student_id)
    {
        $parent_id = Qs::decodeHash($parent_id) ?: (is_numeric($parent_id) ? (int)$parent_id : null);
        $student_id = Qs::decodeHash($student_id) ?: (is_numeric($student_id) ? (int)$student_id : null);

        $parent = User::where('id', $parent_id)->where('user_type', 'parent')->firstOrFail();
        $sr = StudentRecord::where('my_parent_id', $parent->id)
            ->where(function($q) use ($student_id) {
                $q->where('id', $student_id)->orWhere('user_id', $student_id);
            })->firstOrFail();

        $sr->update([
            'my_parent_id' => null,
            'parent_relationship' => null,
        ]);

        $this->auditLog->log(
            'parent.unlink_student',
            'students',
            "Unlinked student '{$sr->user->name}' from parent '{$parent->name}'",
            $sr->user,
            ['parent_id' => $parent->id],
            ['parent_id' => null],
            'success'
        );

        return back()->with('flash_success', __('Student unlinked from parent successfully.'));
    }

    /**
     * Reset parent access and generate a fresh time-limited activation token.
     */
    public function reset_access($id)
    {
        $id = Qs::decodeHash($id) ?: (is_numeric($id) ? (int)$id : null);
        $parent = User::where('id', $id)->where('user_type', 'parent')->firstOrFail();

        $newToken = Str::random(64);
        $parent->update([
            'activation_token' => $newToken,
            'activation_token_expires_at' => now()->addDays(7),
        ]);

        $this->auditLog->log(
            'parent.reset_access',
            'security',
            "Issued new account activation link for parent '{$parent->name}'",
            $parent,
            [],
            ['parent_id' => $parent->id],
            'success'
        );

        $activationUrl = route('parent.activate', $newToken);
        return back()->with('flash_success', __('Fresh activation link generated successfully.'))
            ->with('activation_url', $activationUrl)
            ->with('parent_name', $parent->name);
    }

    /**
     * Toggle active/inactive status of a parent account.
     */
    public function toggle_active($id)
    {
        $id = Qs::decodeHash($id) ?: (is_numeric($id) ? (int)$id : null);
        $parent = User::where('id', $id)->where('user_type', 'parent')->firstOrFail();

        $newStatus = !$parent->is_active;
        $parent->update(['is_active' => $newStatus]);

        $actionText = $newStatus ? 'activated' : 'deactivated';

        $this->auditLog->log(
            'parent.status_change',
            'administration',
            "Parent account '{$parent->name}' was {$actionText}.",
            $parent,
            ['is_active' => !$newStatus],
            ['is_active' => $newStatus],
            'success'
        );

        return back()->with('flash_success', __("Parent account successfully {$actionText}."));
    }

    /**
     * Delete parent account with safety checks (prevent orphaned student dependencies).
     */
    public function destroy($id)
    {
        if (!Qs::userIsSuperAdmin()) {
            abort(403);
        }

        $id = Qs::decodeHash($id) ?: (is_numeric($id) ? (int)$id : null);
        $parent = User::where('id', $id)->where('user_type', 'parent')->firstOrFail();

        // Safety constraint: Prevent deletion if still linked to students
        $linkedCount = $parent->children()->count();
        if ($linkedCount > 0) {
            return back()->with('flash_danger', __('Cannot delete parent account because :count student(s) are still linked to it. Unlink all students first.', ['count' => $linkedCount]));
        }

        $parentName = $parent->name;
        $parent->delete();

        $this->auditLog->log(
            'parent.delete',
            'administration',
            "Deleted parent account '{$parentName}' (ID: {$id})",
            null,
            ['parent_id' => $id, 'name' => $parentName],
            [],
            'success'
        );

        return back()->with('flash_success', __('Parent account deleted successfully.'));
    }
}
