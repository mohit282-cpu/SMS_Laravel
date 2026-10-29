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
            'amt_paid' => ['required', 'numeric', 'gt:0', 'max:1000000000']
        ], [], ['amt_paid' => 'Amount Paid']);

        $pr_id = Qs::decodeHash($pr_id) ?: $pr_id;

        $rawAmt = $req->input('amt_paid');
        if (!is_numeric($rawAmt) || !is_finite((float)$rawAmt) || (float)$rawAmt <= 0) {
            return response()->json(['msg' => __('Payment amount must be a valid positive number.')], 422);
        }

        $idempotencyKey = $req->input('idempotency_key') ?: $req->header('X-Idempotency-Key');
        $cacheKey = null;
        if (!empty($idempotencyKey)) {
            $cacheKey = 'pay_idem_' . md5($idempotencyKey);
            $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
            if ($cached) {
                if ($cached['pr_id'] != $pr_id || abs((float)$cached['amt_paid'] - (float)$rawAmt) > 0.001) {
                    return response()->json([
                        'msg' => __('Idempotency key has already been used with different payment parameters.')
                    ], 422);
                }
                return response()->json([
                    'ok' => true,
                    'msg' => __('Payment already processed.'),
                    'duplicate' => true,
                ], 200);
            }
        }

        return \Illuminate\Support\Facades\DB::transaction(function() use ($req, $pr_id, $rawAmt, $cacheKey) {
            $pr = \App\Models\PaymentRecord::where('id', $pr_id)->lockForUpdate()->first();
            if(!$pr) {
                return response()->json(['msg' => __('msg.rnf')], 404);
            }

            if ($pr->paid) {
                return response()->json(['msg' => __('This invoice has already been fully paid.')], 422);
            }

            $payment = \App\Models\Payment::where('id', $pr->payment_id)->lockForUpdate()->first();
            if(!$payment) {
                return response()->json(['msg' => __('msg.rnf')], 404);
            }

            $payAmt = (float)$rawAmt;
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
            if ($newAmtPaid >= $totalInvoiceAmount) {
                $newAmtPaid = $totalInvoiceAmount;
                $newBalance = 0.0;
                $isPaid = 1;
            } else {
                $newBalance = max(0, round($totalInvoiceAmount - $newAmtPaid, 2));
                $isPaid = $newBalance <= 0.001 ? 1 : 0;
            }

            $d['amt_paid'] = $newAmtPaid;
            $d['balance'] = $newBalance;
            $d['paid'] = $isPaid;

            $this->pay->updateRecord($pr_id, $d);

            $d2['amt_paid'] = $payAmt;
            $d2['balance'] = $newBalance;
            $d2['pr_id'] = $pr_id;
            $d2['year'] = $pr->year ?: ($payment->year ?: $this->year);

            $receipt = $this->pay->createReceipt($d2);
            if (!$receipt) {
                throw new \RuntimeException('Failed to create receipt record.');
            }

            if ($cacheKey) {
                \Illuminate\Support\Facades\Cache::put($cacheKey, [
                    'pr_id' => $pr_id,
                    'amt_paid' => (float)$payAmt,
                ], now()->addMinutes(30));
            }

            app(\App\Services\AuditLogService::class)->log(
                'payment.pay_now',
                'finance',
                "Payment of Rs. {$payAmt} recorded for invoice #{$pr->ref_no}",
                $pr,
                ['amt_paid' => $currentPaid, 'balance' => $outstanding],
                ['amt_paid' => $newAmtPaid, 'balance' => $newBalance, 'paid' => $isPaid],
                'success'
            );

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

        if (!empty($data['due_date_bs'])) {
            $data['due_date'] = app(\App\Services\NepaliDateService::class)->toAdDateString($data['due_date_bs']);
        }

        $payment = $this->pay->create($data);

        app(\App\Services\AuditLogService::class)->log(
            'payment.create',
            'finance',
            "Created fee/payment item '{$data['title']}' for Rs. {$data['amount']}",
            $payment,
            [],
            ['title' => $data['title'], 'amount' => $data['amount'], 'my_class_id' => $data['my_class_id'] ?? null],
            'success'
        );

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

        app(\App\Services\AuditLogService::class)->log(
            'payment.delete',
            'finance',
            "Deleted payment item '{$payment->title}' (ID: {$payment->id})",
            $payment,
            ['title' => $payment->title, 'amount' => $payment->amount],
            [],
            'success'
        );

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
            $oldPaid = $pr->amt_paid;
            $oldBalance = $pr->balance;

            $data['amt_paid'] = 0;
            $data['paid'] = 0;
            $data['balance'] = $payment ? $payment->amount : 0;

            $this->pay->updateRecord($id, $data);
            $this->pay->deleteReceipts(['pr_id' => $id]);

            app(\App\Services\AuditLogService::class)->log(
                'payment.reset',
                'finance',
                "Reset payment record #{$pr->ref_no} (reversal of Rs. {$oldPaid}).",
                $pr,
                ['amt_paid' => $oldPaid, 'balance' => $oldBalance],
                ['amt_paid' => 0, 'balance' => $data['balance']],
                'success'
            );

            return back()->with('flash_success', __('msg.update_ok'));
        });
    }
    public function monthly_billing(Request $req)
    {
        $nepaliDate = app(\App\Services\NepaliDateService::class);
        $todayBs = $nepaliDate->today();

        $selectedYear = (int)$req->input('bs_year', $todayBs['year']);
        $selectedMonth = (int)$req->input('bs_month', $todayBs['month']);
        if ($selectedMonth < 1 || $selectedMonth > 12) {
            $selectedMonth = 1;
        }

        $classId = $req->input('my_class_id');

        $periodRange = $nepaliDate->getBsMonthAdRange($selectedYear, $selectedMonth);
        $months = $nepaliDate->getMonths();

        $query = \App\Models\PaymentRecord::where('bs_year', $selectedYear)
            ->where('bs_month', $selectedMonth)
            ->with(['payment', 'student']);

        if ($classId) {
            $query->whereHas('student.student_record', function($q) use ($classId) {
                $q->where('my_class_id', $classId);
            });
        }

        $records = $query->get();

        $totalBilled = $records->sum(function($r) {
            return (float) ($r->payment ? $r->payment->amount : 0);
        });
        $totalCollected = $records->sum(function($r) {
            return (float) $r->amt_paid;
        });
        $totalBalance = $records->sum(function($r) {
            return (float) $r->balance;
        });
        $totalDiscount = $records->sum(function($r) {
            return (float) ($r->discount_amt ?? 0);
        });

        $clearedCount = $records->where('paid', 1)->count();
        $unclearedCount = $records->where('paid', 0)->count();

        // Eligible students count for current filter
        $studentQuery = \App\Models\StudentRecord::query();
        if ($classId) {
            $studentQuery->where('my_class_id', $classId);
        }
        $eligibleStudentsCount = $studentQuery->count();

        $d = [
            'bs_year' => $selectedYear,
            'bs_month' => $selectedMonth,
            'my_class_id' => $classId,
            'my_classes' => $this->my_class->all(),
            'months' => $months,
            'periodRange' => $periodRange,
            'records' => $records,
            'totalBilled' => $totalBilled,
            'totalCollected' => $totalCollected,
            'totalBalance' => $totalBalance,
            'totalDiscount' => $totalDiscount,
            'clearedCount' => $clearedCount,
            'unclearedCount' => $unclearedCount,
            'eligibleStudentsCount' => $eligibleStudentsCount,
        ];

        return view('pages.support_team.payments.monthly_billing', $d);
    }

    public function generate_monthly_invoices(Request $req)
    {
        $this->validate($req, [
            'bs_year' => 'required|integer|between:2000,2090',
            'bs_month' => 'required|integer|between:1,12',
            'my_class_id' => 'nullable|exists:my_classes,id',
            'fee_category' => 'required|string|in:tuition,transport,hostel,computer_lab,all',
            'amount' => 'nullable|numeric|min:0|max:10000000',
            'due_day' => 'nullable|integer|between:1,32',
        ], [], [
            'bs_year' => 'BS Year',
            'bs_month' => 'BS Month',
            'my_class_id' => 'Class',
            'fee_category' => 'Fee Category',
            'amount' => 'Amount',
            'due_day' => 'Due Day',
        ]);

        $nepaliDate = app(\App\Services\NepaliDateService::class);
        $bsYear = (int)$req->input('bs_year');
        $bsMonth = (int)$req->input('bs_month');
        $classId = $req->input('my_class_id');
        $category = $req->input('fee_category');
        $customAmount = $req->filled('amount') ? (float)$req->input('amount') : null;

        $daysInMonth = $nepaliDate->getDaysInBsMonth($bsYear, $bsMonth);
        $dueDay = (int)($req->input('due_day') ?: 15);
        if ($dueDay > $daysInMonth) {
            $dueDay = $daysInMonth;
        }

        // Calculate AD due date
        $dueDateConv = $nepaliDate->bsToAd($bsYear, $bsMonth, $dueDay);
        $dueDateAd = $dueDateConv['ad_date'];
        $dueDateBs = sprintf('%04d-%02d-%02d', $bsYear, $bsMonth, $dueDay);

        $monthName = $nepaliDate->getBsMonthName($bsMonth, 'en');
        $monthNameNe = $nepaliDate->getBsMonthName($bsMonth, 'ne');

        // Classes to process
        $classes = $classId ? $this->my_class->find($classId) : $this->my_class->all();
        if ($classId && !($classes instanceof \Illuminate\Database\Eloquent\Collection)) {
            $classes = collect([$classes]);
        }

        // Fee categories to process
        $categories = ($category === 'all')
            ? ['tuition', 'transport', 'hostel', 'computer_lab']
            : [$category];

        $sessionYear = $this->year;

        $stats = \Illuminate\Support\Facades\DB::transaction(function() use (
            $classes, $categories, $bsYear, $bsMonth, $monthName, $monthNameNe,
            $dueDateAd, $dueDateBs, $customAmount, $sessionYear
        ) {
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($classes as $cls) {
                // Determine eligible active students in this class
                $students = \App\Models\StudentRecord::where('my_class_id', $cls->id)
                    ->with('user')
                    ->get();

                if ($students->isEmpty()) {
                    continue;
                }

                foreach ($categories as $cat) {
                    $catTitle = ucfirst(str_replace('_', ' ', $cat));
                    $paymentTitle = "Monthly {$catTitle} Fee - {$monthName} {$bsYear} BS ({$monthNameNe})";

                    // Determine amount for this class and category
                    if ($customAmount !== null) {
                        $feeAmount = $customAmount;
                    } else {
                        // Fetch from class type or default settings
                        $clsType = $cls->class_type ? strtolower($cls->class_type->code) : 'c';
                        $settingKey = 'next_term_fees_' . $clsType;
                        $defaultFee = \App\Models\Setting::where('type', $settingKey)->value('description');
                        $feeAmount = $defaultFee ? (float)$defaultFee : 2500.00;
                    }

                    // Find or create recurring Payment fee structure for this class, period, and category
                    $payment = \App\Models\Payment::firstOrCreate(
                        [
                            'my_class_id' => $cls->id,
                            'period_type' => 'monthly',
                            'fee_category' => $cat,
                            'bs_year' => $bsYear,
                            'bs_month' => $bsMonth,
                        ],
                        [
                            'title' => $paymentTitle,
                            'amount' => $feeAmount,
                            'ref_no' => \App\Helpers\Pay::genRefCode(),
                            'method' => 'cash',
                            'description' => "Monthly {$catTitle} charge for {$monthName} {$bsYear} BS",
                            'year' => $sessionYear,
                            'due_date' => $dueDateAd,
                            'due_date_bs' => $dueDateBs,
                        ]
                    );

                    // For each student in the class, generate PaymentRecord invoice idempotently
                    foreach ($students as $st) {
                        $existing = \App\Models\PaymentRecord::where('payment_id', $payment->id)
                            ->where('student_id', $st->user_id)
                            ->first();

                        if ($existing) {
                            $skippedCount++;
                            continue;
                        }

                        $pr = new \App\Models\PaymentRecord();
                        $pr->payment_id = $payment->id;
                        $pr->student_id = $st->user_id;
                        $pr->ref_no = mt_rand(10000000, 99999999);
                        $pr->amt_paid = 0.00;
                        $pr->balance = (float)$payment->amount;
                        $pr->paid = 0;
                        $pr->year = $payment->year;
                        $pr->bs_year = $bsYear;
                        $pr->bs_month = $bsMonth;
                        $pr->due_date = $dueDateAd;
                        $pr->due_date_bs = $dueDateBs;
                        $pr->discount_amt = 0.00;
                        $pr->save();

                        $createdCount++;
                    }
                }
            }

            return ['created' => $createdCount, 'skipped' => $skippedCount];
        });

        $msg = __('Successfully generated :created monthly fee invoice(s). (:skipped already existed)', [
            'created' => $stats['created'],
            'skipped' => $stats['skipped'],
        ]);

        if ($req->ajax()) {
            return response()->json([
                'ok' => true,
                'msg' => $msg,
                'created' => $stats['created'],
                'skipped' => $stats['skipped'],
            ]);
        }

        return redirect()->route('payments.monthly_billing', [
            'bs_year' => $bsYear,
            'bs_month' => $bsMonth,
            'my_class_id' => $classId,
        ])->with('flash_success', $msg);
    }

    public function reports(Request $req)
    {
        $nepaliDate = app(\App\Services\NepaliDateService::class);
        $todayBs = $nepaliDate->today();

        $selectedYear = (int)$req->input('bs_year', $todayBs['year']);
        $classId = $req->input('my_class_id');

        $months = $nepaliDate->getMonths();

        // Calculate summary for each of the 12 BS months
        $monthlySummaries = [];
        $grandTotalBilled = 0;
        $grandTotalCollected = 0;
        $grandTotalBalance = 0;

        foreach ($months as $mNum => $mInfo) {
            $range = $nepaliDate->getBsMonthAdRange($selectedYear, $mNum);

            $query = \App\Models\PaymentRecord::where('bs_year', $selectedYear)
                ->where('bs_month', $mNum)
                ->with('payment');

            if ($classId) {
                $query->whereHas('student.student_record', function($q) use ($classId) {
                    $q->where('my_class_id', $classId);
                });
            }

            $records = $query->get();

            $billed = $records->sum(function($r) {
                return (float) ($r->payment ? $r->payment->amount : 0);
            });
            $collected = $records->sum(function($r) {
                return (float) $r->amt_paid;
            });
            $balance = $records->sum(function($r) {
                return (float) $r->balance;
            });
            $invoiceCount = $records->count();
            $paidCount = $records->where('paid', 1)->count();

            $rate = ($billed > 0) ? round(($collected / $billed) * 100, 1) : 0;

            $grandTotalBilled += $billed;
            $grandTotalCollected += $collected;
            $grandTotalBalance += $balance;

            $monthlySummaries[$mNum] = [
                'month_num' => $mNum,
                'name_en' => $mInfo['en'],
                'name_ne' => $mInfo['ne'],
                'start_ad' => $range['start_ad'],
                'end_ad' => $range['end_ad'],
                'days' => $range['days'],
                'billed' => $billed,
                'collected' => $collected,
                'balance' => $balance,
                'invoice_count' => $invoiceCount,
                'paid_count' => $paidCount,
                'rate' => $rate,
            ];
        }

        // Class-wise breakdown for selected year
        $classBreakdown = [];
        $classes = $this->my_class->all();
        foreach ($classes as $cls) {
            $clsQuery = \App\Models\PaymentRecord::where('bs_year', $selectedYear)
                ->whereHas('student.student_record', function($q) use ($cls) {
                    $q->where('my_class_id', $cls->id);
                })
                ->with('payment');

            $clsRecords = $clsQuery->get();
            $clsBilled = $clsRecords->sum(function($r) {
                return (float) ($r->payment ? $r->payment->amount : 0);
            });
            $clsCollected = $clsRecords->sum(function($r) {
                return (float) $r->amt_paid;
            });
            $clsBalance = $clsRecords->sum(function($r) {
                return (float) $r->balance;
            });

            $classBreakdown[] = [
                'class_name' => $cls->name,
                'billed' => $clsBilled,
                'collected' => $clsCollected,
                'balance' => $clsBalance,
                'invoices' => $clsRecords->count(),
            ];
        }

        $overallRate = ($grandTotalBilled > 0) ? round(($grandTotalCollected / $grandTotalBilled) * 100, 1) : 0;

        $d = [
            'bs_year' => $selectedYear,
            'my_class_id' => $classId,
            'my_classes' => $classes,
            'monthlySummaries' => $monthlySummaries,
            'classBreakdown' => $classBreakdown,
            'grandTotalBilled' => $grandTotalBilled,
            'grandTotalCollected' => $grandTotalCollected,
            'grandTotalBalance' => $grandTotalBalance,
            'overallRate' => $overallRate,
        ];

        return view('pages.support_team.payments.reports', $d);
    }
}
