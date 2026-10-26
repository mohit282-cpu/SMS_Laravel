<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\MyClass;
use App\Models\Notice;
use App\Repositories\MyClassRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NoticeController extends Controller
{
    protected $my_class;

    public function __construct(MyClassRepo $my_class)
    {
        $this->middleware('auth');
        $this->middleware('teamSA', ['only' => ['create', 'store', 'edit', 'update']]);
        $this->middleware('super_admin', ['only' => ['destroy']]);

        $this->my_class = $my_class;
    }

    public function index()
    {
        $user = Auth::user();
        $notices = Notice::visibleTo($user)
            ->with(['creator', 'my_class', 'section'])
            ->orderBy('notice_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(15);

        $my_classes = $this->my_class->all();

        return view('pages.support_team.notices.index', compact('notices', 'my_classes'));
    }

    public function create()
    {
        $my_classes = $this->my_class->all();
        return view('pages.support_team.notices.create', compact('my_classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_date_bs' => 'nullable|string|max:20',
            'notice_date' => 'nullable|date',
            'expiry_date_bs' => 'nullable|string|max:20',
            'expiry_date' => 'nullable|date',
            'role_visibility' => 'nullable|string|in:all,teacher,student,parent,accountant,librarian',
            'my_class_id' => 'nullable|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->filled('section_id')) {
            if (!$request->filled('my_class_id')) {
                return back()->withInput()->with('flash_danger', __('A class must be selected if a section is specified.'));
            }
            $sec = \App\Models\Section::where('id', $request->section_id)->where('my_class_id', $request->my_class_id)->first();
            if (!$sec) {
                return back()->withInput()->with('flash_danger', __('The selected section does not belong to the selected class.'));
            }
        }

        $data = $validated;
        $data['created_by'] = Auth::id();
        $data['role_visibility'] = $data['role_visibility'] ?? 'all';
        $data['is_published'] = $request->has('is_published') ? (bool)$request->is_published : true;

        if (!empty($data['notice_date_bs'])) {
            $adNotice = NepaliHelper::bsToAd($data['notice_date_bs']);
            if (!$adNotice) {
                return back()->withInput()->with('flash_danger', __('Invalid BS notice date format or out-of-range date.'));
            }
            $data['notice_date'] = $adNotice;
        } elseif (empty($data['notice_date'])) {
            $data['notice_date'] = now()->toDateString();
            $data['notice_date_bs'] = NepaliHelper::adToBs($data['notice_date']);
        } else {
            $data['notice_date_bs'] = NepaliHelper::adToBs($data['notice_date']);
        }

        if (!empty($data['expiry_date_bs'])) {
            $adExpiry = NepaliHelper::bsToAd($data['expiry_date_bs']);
            if (!$adExpiry) {
                return back()->withInput()->with('flash_danger', __('Invalid BS expiry date format or out-of-range date.'));
            }
            $data['expiry_date'] = $adExpiry;
        } elseif (!empty($data['expiry_date'])) {
            $data['expiry_date_bs'] = NepaliHelper::adToBs($data['expiry_date']);
        } else {
            $data['expiry_date'] = null;
            $data['expiry_date_bs'] = null;
        }

        if (!empty($data['expiry_date']) && !empty($data['notice_date'])) {
            if ($data['expiry_date'] < $data['notice_date']) {
                return back()->withInput()->with('flash_danger', __('Expiry date cannot be earlier than notice date.'));
            }
        }

        Notice::create($data);

        return redirect()->route('notices.index')->with('flash_success', __('Notice published successfully.'));
    }

    public function show($id)
    {
        $user = Auth::user();
        $notice = Notice::visibleTo($user)->with(['creator', 'my_class', 'section'])->findOrFail($id);

        return view('pages.support_team.notices.show', compact('notice'));
    }

    public function edit($id)
    {
        $notice = Notice::findOrFail($id);
        $my_classes = $this->my_class->all();

        return view('pages.support_team.notices.edit', compact('notice', 'my_classes'));
    }

    public function update(Request $request, $id)
    {
        $notice = Notice::findOrFail($id);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'notice_date_bs' => 'nullable|string|max:20',
            'notice_date' => 'nullable|date',
            'expiry_date_bs' => 'nullable|string|max:20',
            'expiry_date' => 'nullable|date',
            'role_visibility' => 'nullable|string|in:all,teacher,student,parent,accountant,librarian',
            'my_class_id' => 'nullable|exists:my_classes,id',
            'section_id' => 'nullable|exists:sections,id',
            'is_published' => 'nullable|boolean',
        ]);

        if ($request->filled('section_id')) {
            $classId = $request->filled('my_class_id') ? $request->my_class_id : $notice->my_class_id;
            if (!$classId) {
                return back()->withInput()->with('flash_danger', __('A class must be selected if a section is specified.'));
            }
            $sec = \App\Models\Section::where('id', $request->section_id)->where('my_class_id', $classId)->first();
            if (!$sec) {
                return back()->withInput()->with('flash_danger', __('The selected section does not belong to the selected class.'));
            }
        }

        $data = $validated;
        $data['role_visibility'] = $data['role_visibility'] ?? $notice->role_visibility ?? 'all';
        $data['is_published'] = $request->has('is_published') ? (bool)$request->is_published : $notice->is_published;

        if (!empty($data['notice_date_bs'])) {
            $adNotice = NepaliHelper::bsToAd($data['notice_date_bs']);
            if (!$adNotice) {
                return back()->withInput()->with('flash_danger', __('Invalid BS notice date format or out-of-range date.'));
            }
            $data['notice_date'] = $adNotice;
        } elseif (!empty($data['notice_date'])) {
            $data['notice_date_bs'] = NepaliHelper::adToBs($data['notice_date']);
        }

        if (!empty($data['expiry_date_bs'])) {
            $adExpiry = NepaliHelper::bsToAd($data['expiry_date_bs']);
            if (!$adExpiry) {
                return back()->withInput()->with('flash_danger', __('Invalid BS expiry date format or out-of-range date.'));
            }
            $data['expiry_date'] = $adExpiry;
        } elseif (!empty($data['expiry_date'])) {
            $data['expiry_date_bs'] = NepaliHelper::adToBs($data['expiry_date']);
        } else {
            $data['expiry_date'] = null;
            $data['expiry_date_bs'] = null;
        }

        if (!empty($data['expiry_date']) && !empty($data['notice_date'])) {
            if ($data['expiry_date'] < $data['notice_date']) {
                return back()->withInput()->with('flash_danger', __('Expiry date cannot be earlier than notice date.'));
            }
        }

        $notice->update($data);

        return redirect()->route('notices.index')->with('flash_success', __('Notice updated successfully.'));
    }

    public function destroy($id)
    {
        $notice = Notice::findOrFail($id);
        $notice->delete();

        return redirect()->route('notices.index')->with('flash_success', __('Notice deleted successfully.'));
    }
}
