<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\NepaliHelper;
use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\FinanceLedger;
use App\Models\FiscalYear;
use App\Models\SchoolProfile;
use App\Models\StaffPayroll;
use App\Services\ErpSupportService;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->erp = $erp;
    }

    public function index()
    {
        $user = Auth::user();
        if (!Qs::userIsStaff()) {
            abort(403);
        }

        $query = StaffPayroll::with(['staff', 'fiscalYear', 'processor'])->orderBy('id', 'desc');
        if (!Qs::userIsTeamAccount()) {
            $query->where('user_id', $user->id);
        }

        $payrolls = $query->paginate(15);
        $staffList = Qs::userIsTeamAccount()
            ? User::whereIn('user_type', Qs::getStaff())->orderBy('name')->get()
            : collect();
        $fiscalYears = FiscalYear::orderBy('is_active', 'desc')->get();

        $totalNetPaid = (float) StaffPayroll::where('payment_status', 'paid')->sum('net_salary');
        $pendingPayrolls = StaffPayroll::where('payment_status', 'pending')->count();
        $currentMonthBs = substr((string) NepaliHelper::adToBs(now()->toDateString()), 0, 7);

        return view('pages.support_team.payroll.index', compact(
            'payrolls', 'staffList', 'fiscalYears',
            'totalNetPaid', 'pendingPayrolls', 'currentMonthBs'
        ));
    }

    public function store(Request $request)
    {
        if (!Qs::userIsTeamAccount()) {
            abort(403);
        }

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'salary_month_bs' => 'required|string|max:30',
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'basic_salary' => 'required|numeric|min:0|max:9999999',
            'allowances' => 'nullable|numeric|min:0|max:9999999',
            'deductions' => 'nullable|numeric|min:0|max:9999999',
            'provident_fund' => 'nullable|numeric|min:0|max:9999999',
            'tax_amount' => 'nullable|numeric|min:0|max:9999999',
            'payment_method' => 'nullable|string|max:50',
            'remarks' => 'nullable|string|max:1000',
        ]);

        $basic = (float) $validated['basic_salary'];
        $allow = (float) ($validated['allowances'] ?? 0);
        $deduct = (float) ($validated['deductions'] ?? 0);
        $pf = (float) ($validated['provident_fund'] ?? 0);
        $tax = (float) ($validated['tax_amount'] ?? 0);
        $net = max(0, ($basic + $allow) - ($deduct + $pf + $tax));

        $payroll = StaffPayroll::updateOrCreate(
            ['user_id' => $validated['user_id'], 'salary_month_bs' => $validated['salary_month_bs']],
            [
                'fiscal_year_id' => $validated['fiscal_year_id'] ?? optional(FiscalYear::current())->id,
                'basic_salary' => $basic,
                'allowances' => $allow,
                'deductions' => $deduct,
                'provident_fund' => $pf,
                'tax_amount' => $tax,
                'net_salary' => $net,
                'payment_method' => $validated['payment_method'] ?? 'bank_transfer',
                'remarks' => $validated['remarks'] ?? null,
                'processed_by' => Auth::id(),
            ]
        );

        $this->erp->audit('payroll.generate', 'finance', "Generated payroll for staff #{$payroll->user_id} ({$payroll->salary_month_bs})", $payroll);

        return back()->with('flash_success', __('Payroll generated. Net Salary: ') . NepaliHelper::formatNpr($net));
    }

    public function markPaid($id)
    {
        if (!Qs::userIsTeamAccount()) {
            abort(403);
        }

        $payroll = StaffPayroll::with('staff')->findOrFail($id);
        if ($payroll->payment_status === 'paid') {
            return back()->with('flash_info', __('Payroll already marked paid.'));
        }

        $dates = $this->erp->resolveDualDates(null, null, true);

        DB::transaction(function () use ($payroll, $dates) {
            $payroll->update([
                'payment_status' => 'paid',
                'paid_date_ad' => $dates['ad'],
                'paid_date_bs' => $dates['bs'],
                'processed_by' => Auth::id(),
            ]);

            FinanceLedger::create([
                'fiscal_year_id' => $payroll->fiscal_year_id,
                'entry_date_ad' => $dates['ad'],
                'entry_date_bs' => $dates['bs'],
                'entry_type' => 'expense',
                'voucher_type' => 'payment',
                'category' => 'Staff Salary Payroll',
                'account_head' => $payroll->payment_method === 'cash' ? 'cash' : 'bank',
                'amount' => $payroll->net_salary,
                'reference_no' => 'PAYROLL-' . $payroll->id,
                'party_name' => $payroll->staff->name ?? ('Staff #' . $payroll->user_id),
                'description' => "Salary disbursement for {$payroll->salary_month_bs}",
                'recorded_by' => Auth::id(),
            ]);
        });

        return back()->with('flash_success', __('Salary marked paid and posted to Accounting Day Book.'));
    }

    public function payslip($id)
    {
        $payroll = StaffPayroll::with(['staff', 'fiscalYear'])->findOrFail($id);
        if (!Qs::userIsTeamAccount() && (int) $payroll->user_id !== (int) Auth::id()) {
            abort(403);
        }

        $school = SchoolProfile::current();
        return view('pages.support_team.payroll.payslip', compact('payroll', 'school'));
    }
}
