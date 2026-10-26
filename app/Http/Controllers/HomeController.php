<?php

namespace App\Http\Controllers;

use App\Helpers\Qs;
use App\Repositories\UserRepo;

class HomeController extends Controller
{
    protected $user;
    public function __construct(UserRepo $user)
    {
        $this->user = $user;
    }


    public function index()
    {
        return redirect()->route('dashboard');
    }

    public function privacy_policy()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.privacy_policy', $data);
    }

    public function terms_of_use()
    {
        $data['app_name'] = config('app.name');
        $data['app_url'] = config('app.url');
        $data['contact_phone'] = Qs::getSetting('phone');
        return view('pages.other.terms_of_use', $data);
    }

    public function dashboard()
    {
        $d = [];
        $user = auth()->user();

        if (Qs::userIsTeamSA()) {
            $d['users'] = $this->user->getAll();
            $d['total_classes'] = \App\Models\MyClass::count();
            $d['total_sections'] = \App\Models\Section::count();
        } elseif (Qs::userIsTeacher()) {
            $d['my_sections'] = \App\Models\Section::where('teacher_id', $user->id)->with('my_class')->get();
            $d['my_subjects'] = \App\Models\Subject::where('teacher_id', $user->id)->with('my_class')->get();
            $d['my_materials_count'] = \App\Models\StudyMaterial::where('teacher_id', $user->id)->count();
        } elseif (Qs::userIsAccountant()) {
            $session = Qs::getCurrentSession();
            $d['total_payments'] = \App\Models\Payment::where('year', $session)->count();
            $d['total_receipts_amount'] = \App\Models\Receipt::where('year', $session)->sum('amt_paid');
            $d['cleared_records'] = \App\Models\PaymentRecord::where(['year' => $session, 'paid' => 1])->count();
            $d['uncleared_records'] = \App\Models\PaymentRecord::where(['year' => $session, 'paid' => 0])->count();
        } elseif (Qs::userIsLibrarian()) {
            $d['total_books'] = \App\Models\Book::count();
            $d['issued_books'] = \App\Models\BookRequest::where('returned', 0)->count();
            $d['pending_requests'] = \App\Models\BookRequest::where('status', 'pending')->count();
            $d['total_categories'] = \App\Models\BookCategory::count();
        } elseif (Qs::userIsStudent()) {
            $d['student_record'] = \App\Models\StudentRecord::where('user_id', $user->id)->with(['my_class', 'section'])->first();
            $d['my_payments'] = \App\Models\PaymentRecord::where('student_id', $user->id)->get();
            $d['my_books'] = \App\Models\BookRequest::where('user_id', $user->id)->where('returned', 0)->with('book')->get();
            $d['my_materials_count'] = \App\Models\StudyMaterial::visibleTo($user)->count();
        } elseif (Qs::userIsParent()) {
            $d['children'] = \App\Models\StudentRecord::where('my_parent_id', $user->id)->with(['user', 'my_class', 'section'])->get();
        }

        return view('pages.support_team.dashboard', $d);
    }

    public function set_locale($lang)
    {
        if (in_array($lang, ['ne', 'en'], true)) {
            session()->put('locale', $lang);

            if (auth()->check()) {
                auth()->user()->update(['locale' => $lang]);
            }
        }

        return redirect()->back();
    }
}
