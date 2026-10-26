<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Helpers\Pay;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\PaymentCreate;
use App\Http\Requests\Payment\PaymentUpdate;
use App\Models\Setting;
use App\Repositories\MyClassRepo;
use App\Repositories\PaymentRepo;
use App\Repositories\StudentRepo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;

class PaymentController extends Controller
{
    protected $my_class, $pay, $student, $year;

    public function __construct(MyClassRepo $my_class, PaymentRepo $pay, StudentRepo $student)
    {
        $this->my_class = $my_class;
        $this->pay = $pay;
        $this->year = Qs::getCurrentSession();
        $this->student = $student;

        $this->middleware('teamAccount')->except(['invoice', 'receipts', 'pdf_receipts']);
        $this->middleware('super_admin', ['only' => ['destroy', 'reset_record']]);
    }

    public function index()
    {
        $d['selected'] = false;
        $d['years'] = $this->pay->getPaymentYears();

        return view('pages.support_team.payments.index', $d);
    }

    public function show($year)
    {
        $d['payments'] = $this->pay->getPayment(['year' => $year])->get();
        $d['selected'] = true;
        $d['my_classes'] = $this->my_class->all();
        $d['years'] = $this->pay->getPaymentYears();
        $d['year'] = $year;

        return view('pages.support_team.payments.index', $d);
    }

    public function select_year(Request $req)
    {
        return Qs::goToRoute(['payments.show', $req->year]);
    }

    public function create()
    {
        $d['my_classes'] = $this->my_class->all();
        return view('pages.support_team.payments.create', $d);
    }

    public function invoice($st_id, $year = NULL)
    {
        if(!$st_id) {return Qs::goWithDanger();}

        $st_id = Qs::decodeHash($st_id) ?: $st_id;
        if(!$st_id) {return Qs::goWithDanger();}

        if (!Qs::userIsTeamAccount()) {
            if (Qs::userIsStudent() && \Illuminate\Support\Facades\Auth::id() != $st_id) {
                abort(403);
            }
            if (Qs::userIsParent() && !Qs::userIsMyChild($st_id, \Illuminate\Support\Facades\Auth::id())) {
                abort(403);
            }
            if (!Qs::userIsStudent() && !Qs::userIsParent()) {
                abort(403);
            }
        }

        $d['sr'] = $sr = $this->student->findByUserId($st_id)->first();
        if(!$sr) {
            return Qs::goWithDanger('payments.manage');
        }

        $inv = $year ? $this->pay->getAllMyPR($st_id, $year) : $this->pay->getAllMyPR($st_id);

        $pr = $inv->get();
        $d['uncleared'] = $pr->where('paid', 0);
        $d['cleared'] = $pr->where('paid', 1);

        return view('pages.support_team.payments.invoice', $d);
    }

    public function receipts($pr_id)
    {
        if(!$pr_id) {return Qs::goWithDanger();}
        $pr_id = Qs::decodeHash($pr_id) ?: $pr_id;

        try {
             $d['pr'] = $pr = $this->pay->getRecord(['id' => $pr_id])->with('receipt')->first();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        if(!$pr) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        if (!Qs::userIsTeamAccount()) {
            if (Qs::userIsStudent() && \Illuminate\Support\Facades\Auth::id() != $pr->student_id) {
                abort(403);
            }
            if (Qs::userIsParent() && !Qs::userIsMyChild($pr->student_id, \Illuminate\Support\Facades\Auth::id())) {
                abort(403);
            }
            if (!Qs::userIsStudent() && !Qs::userIsParent()) {
                abort(403);
            }
        }

        $d['receipts'] = $pr->receipt;
        $d['payment'] = $pr->payment;
        $d['sr'] = $this->student->findByUserId($pr->student_id)->first();
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });

        return view('pages.support_team.payments.receipt', $d);
    }

    public function pdf_receipts($pr_id)
    {
        if(!$pr_id) {return Qs::goWithDanger();}
        $pr_id = Qs::decodeHash($pr_id) ?: $pr_id;

        try {
            $d['pr'] = $pr = $this->pay->getRecord(['id' => $pr_id])->with('receipt')->first();
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $ex) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        if(!$pr) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        if (!Qs::userIsTeamAccount()) {
            if (Qs::userIsStudent() && \Illuminate\Support\Facades\Auth::id() != $pr->student_id) {
                abort(403);
            }
            if (Qs::userIsParent() && !Qs::userIsMyChild($pr->student_id, \Illuminate\Support\Facades\Auth::id())) {
                abort(403);
            }
            if (!Qs::userIsStudent() && !Qs::userIsParent()) {
                abort(403);
            }
        }

        $d['receipts'] = $pr->receipt;
        $d['payment'] = $pr->payment;
        $d['sr'] = $sr =$this->student->findByUserId($pr->student_id)->first();
        $d['s'] = Setting::all()->flatMap(function($s){
            return [$s->type => $s->description];
        });

        $pdf_name = 'Receipt_'.$pr->ref_no;

        return PDF::loadView('pages.support_team.payments.receipt', $d)->download($pdf_name);
    }

    protected function downloadReceipt($page, $data, $name = NULL){
        $path = 'receipts/file.html';
        $disk = Storage::disk('local');
        $disk->put($path, view($page, $data) );
        $html = $disk->get($path);
        return PDF::loadHTML($html)->download($name);
    }

    public function pay_now(Request $req, $pr_id)
    {
        $this->validate($req, [
            'amt_paid' => 'required|numeric|gt:0'
        ], [], ['amt_paid' => 'Amount Paid']);

        $pr_id = Qs::decodeHash($pr_id) ?: $pr_id;

        return \Illuminate\Support\Facades\DB::transaction(function() use ($req, $pr_id) {
            $pr = \App\Models\PaymentRecord::where('id', $pr_id)->lockForUpdate()->first();
            if(!$pr) {
                return response()->json(['msg' => __('msg.rnf')], 404);
            }

            if ($pr->paid) {
                return response()->json(['msg' => __('This invoice has already been fully paid.')], 422);
            }

            $payment = $this->pay->find($pr->payment_id);
            if(!$payment) {
                return response()->json(['msg' => __('msg.rnf')], 404);
            }

            $payAmt = (float)$req->amt_paid;
            if ($payAmt <= 0 || !is_finite($payAmt)) {
                return response()->json(['msg' => __('Payment amount must be a positive number.')], 422);
            }

            // Round to 2 decimal places to match currency schema
            $payAmt = round($payAmt, 2);

            // Authoritative outstanding balance
            $totalInvoiceAmount = (float)$payment->amount;
            $currentPaid = (float)$pr->amt_paid;
            $outstanding = max(0, round($totalInvoiceAmount - $currentPaid, 2));

            if ($outstanding <= 0) {
                if (!$pr->paid) {
                    $this->pay->updateRecord($pr_id, ['paid' => 1, 'balance' => 0]);
                }
                return response()->json(['msg' => __('This invoice has already been fully paid.')], 422);
            }

            // Strict server-side rejection of overpayments
            if ($payAmt > $outstanding) {
                return response()->json([
                    'msg' => __('Payment amount exceeds the outstanding balance of :amount', ['amount' => $outstanding])
                ], 422);
            }

            $newAmtPaid = round($currentPaid + $payAmt, 2);
            $newBalance = max(0, round($totalInvoiceAmount - $newAmtPaid, 2));
            $isPaid = $newBalance <= 0.001 ? 1 : 0;

            $d['amt_paid'] = $newAmtPaid;
            $d['balance'] = $newBalance;
            $d['paid'] = $isPaid;

            $this->pay->updateRecord($pr_id, $d);

            $d2['amt_paid'] = $payAmt;
            $d2['balance'] = $newBalance;
            $d2['pr_id'] = $pr_id;
            $d2['year'] = $pr->year ?: $payment->year ?: $this->year;

            $this->pay->createReceipt($d2);
            return Qs::jsonUpdateOk();
        });
    }

    public function manage($class_id = NULL)
    {
        $d['my_classes'] = $this->my_class->all();
        $d['selected'] = false;

        if($class_id){
            $d['students'] = $st = $this->student->getRecord(['my_class_id' => $class_id])->get()->sortBy('user.name');
            if($st->count() < 1){
                return Qs::goWithDanger('payments.manage');
            }
            $d['selected'] = true;
            $d['my_class_id'] = $class_id;
        }

        return view('pages.support_team.payments.manage', $d);
    }

    public function select_class(Request $req)
    {
        $this->validate($req, [
            'my_class_id' => 'required|exists:my_classes,id'
        ], [], ['my_class_id' => 'Class']);

        $wh['my_class_id'] = $class_id = $req->my_class_id;

        $pay1 = $this->pay->getPayment(['my_class_id' => $class_id, 'year' => $this->year])->get();
        $pay2 = $this->pay->getGeneralPayment(['year' => $this->year])->get();
        $payments = $pay2->count() ? $pay1->merge($pay2) : $pay1;
        $students = $this->student->getRecord($wh)->get();

        if($payments->count() && $students->count()){
            foreach($payments as $p){
                foreach($students as $st){
                    $pr['student_id'] = $st->user_id;
                    $pr['payment_id'] = $p->id;
                    $pr['year'] = $this->year;
                    $rec = $this->pay->createRecord($pr);
                    $rec->ref_no ?: $rec->update(['ref_no' => mt_rand(100000, 99999999)]);

                }
            }
        }

        return Qs::goToRoute(['payments.manage', $class_id]);
    }

    public function store(PaymentCreate $req)
    {
        $data = $req->all();
        $data['year'] = $this->year;
        $data['ref_no'] = Pay::genRefCode();
        $this->pay->create($data);

        return Qs::jsonStoreOk();
    }

    public function edit($id)
    {
        $d['payment'] = $pay = $this->pay->find($id);

        return is_null($pay) ? Qs::goWithDanger('payments.index') : view('pages.support_team.payments.edit', $d);
    }

    public function update(PaymentUpdate $req, $id)
    {
        $data = $req->all();
        $this->pay->update($id, $data);

        return Qs::jsonUpdateOk();
    }

    public function destroy($id)
    {
        $payment = $this->pay->find($id);
        if (!$payment) {
            return Qs::goWithDanger('payments.index');
        }

        $hasPaidRecords = \App\Models\PaymentRecord::where('payment_id', $id)->where('amt_paid', '>', 0)->exists();
        if ($hasPaidRecords) {
            return back()->with('flash_danger', __('Cannot delete fee structure with existing payments or receipts.'));
        }

        $payment->delete();

        return Qs::deleteOk('payments.index');
    }

    public function reset_record($id)
    {
        $id = Qs::decodeHash($id) ?: $id;

        return \Illuminate\Support\Facades\DB::transaction(function() use ($id) {
            $pr = $this->pay->findRecord($id);
            if (!$pr) {
                return back()->with('flash_danger', __('msg.rnf'));
            }

            $payment = $this->pay->find($pr->payment_id);
            $data['amt_paid'] = 0;
            $data['paid'] = 0;
            $data['balance'] = $payment ? $payment->amount : 0;

            $this->pay->updateRecord($id, $data);
            $this->pay->deleteReceipts(['pr_id' => $id]);

            return back()->with('flash_success', __('msg.update_ok'));
        });
    }
}
