<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\FeeConcession;
use App\Models\FinanceLedger;
use App\Models\FiscalYear;
use App\Models\StudentRecord;
use App\Services\ErpSupportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountingController extends Controller
{
    protected ErpSupportService $erp;

    public function __construct(ErpSupportService $erp)
    {
        $this->middleware('auth');
        $this->middleware('teamAccount');
        $this->erp = $erp;
    }

    public function index(Request $request)
    {
        $fiscalYears = FiscalYear::orderBy('is_active', 'desc')->orderBy('id', 'desc')->get();
        $activeFiscalYear = $fiscalYears->firstWhere('is_active', true);

        $query = FinanceLedger::with(['fiscalYear', 'recorder'])
            ->orderBy('entry_date_ad', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('fiscal_year_id')) {
            $query->where('fiscal_year_id', $request->integer('fiscal_year_id'));
        }
        if ($request->filled('entry_type')) {
            $query->where('entry_type', $request->query('entry_type'));
        }
        if ($request->filled('account_head')) {
            $query->where('account_head', $request->query('account_head'));
        }

        $entries = $query->paginate(20)->appends($request->query());

        $totalIncome = (float) FinanceLedger::where('entry_type', 'income')->sum('amount');
        $totalExpense = (float) FinanceLedger::where('entry_type', 'expense')->sum('amount');
        $netBalance = $totalIncome - $totalExpense;
        $cashBalance = (float) FinanceLedger::where('account_head', 'cash')->where('entry_type', 'income')->sum('amount')
            - (float) FinanceLedger::where('account_head', 'cash')->where('entry_type', 'expense')->sum('amount');

        $concessions = FeeConcession::with(['studentRecord.user', 'studentRecord.my_class', 'approver'])
            ->orderBy('id', 'desc')
            ->take(50)
            ->get();
        $students = StudentRecord::with(['user', 'my_class'])->where('grad', 0)->orderBy('adm_no')->get();

        return view('pages.support_team.accounting.index', compact(
            'entries', 'fiscalYears', 'activeFiscalYear',
            'totalIncome', 'totalExpense', 'netBalance', 'cashBalance',
            'concessions', 'students'
        ));
    }

    public function storeLedger(Request $request)
    {
        $validated = $request->validate([
            'fiscal_year_id' => 'nullable|exists:fiscal_years,id',
            'entry_type' => 'required|in:income,expense',
            'voucher_type' => 'nullable|in:receipt,payment,journal,contra,purchase,sales',
            'category' => 'required|string|max:100',
            'account_head' => 'required|in:cash,bank',
            'amount' => 'required|numeric|min:0.01|max:999999999',
            'entry_date_ad' => 'nullable|date',
            'entry_date_bs' => 'nullable|string|max:10',
            'reference_no' => 'nullable|string|max:80',
            'party_name' => 'nullable|string|max:150',
            'description' => 'nullable|string|max:2000',
        ]);

        $dates = $this->erp->resolveDualDates($validated['entry_date_ad'] ?? null, $validated['entry_date_bs'] ?? null, true);
        $activeFy = $validated['fiscal_year_id'] ?? optional(FiscalYear::current())->id;

        $entry = FinanceLedger::create([
            'fiscal_year_id' => $activeFy,
            'entry_date_ad' => $dates['ad'],
            'entry_date_bs' => $dates['bs'],
            'entry_type' => $validated['entry_type'],
            'voucher_type' => $validated['voucher_type'] ?? ($validated['entry_type'] === 'income' ? 'receipt' : 'payment'),
            'category' => $validated['category'],
            'account_head' => $validated['account_head'],
            'amount' => $validated['amount'],
            'reference_no' => $validated['reference_no'] ?? null,
            'party_name' => $validated['party_name'] ?? null,
            'description' => $validated['description'] ?? null,
            'recorded_by' => Auth::id(),
        ]);

        $this->erp->audit('accounting.voucher', 'finance', "Recorded {$entry->entry_type} voucher ({$entry->category})", $entry, ['amount' => $entry->amount]);

        return back()->with('flash_success', __('Accounting voucher saved to Day Book ledger.'));
    }

    public function storeFiscalYear(Request $request)
    {
        if (!Qs::userIsTeamSA()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:fiscal_years,name',
            'start_date_bs' => 'required|string|max:10',
            'end_date_bs' => 'required|string|max:10',
        ]);

        if ($request->boolean('is_active')) {
            FiscalYear::query()->update(['is_active' => false]);
        }

        FiscalYear::create([
            'name' => $validated['name'],
            'start_date_bs' => $validated['start_date_bs'],
            'end_date_bs' => $validated['end_date_bs'],
            'is_active' => $request->boolean('is_active', true),
            'is_closed' => false,
        ]);

        return back()->with('flash_success', __('Fiscal year created.'));
    }

    public function storeConcession(Request $request)
    {
        $validated = $request->validate([
            'student_record_id' => 'required|exists:student_records,id',
            'title' => 'required|string|max:150',
            'concession_type' => 'required|in:fixed,percentage',
            'amount_or_percent' => 'required|numeric|min:0.01|max:999999',
            'reason' => 'nullable|string|max:1000',
        ]);

        FeeConcession::create($validated + [
            'academic_year' => Qs::getCurrentSession(),
            'is_active' => true,
            'approved_by' => Auth::id(),
        ]);

        return back()->with('flash_success', __('Scholarship / Fee concession applied.'));
    }
}
