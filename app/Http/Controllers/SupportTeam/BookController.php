<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Http\Requests\Book\BookCreateRequest;
use App\Http\Requests\Book\BookIssueRequest;
use App\Http\Requests\Book\BookUpdateRequest;
use App\Models\Book;
use App\Repositories\BookRepo;
use App\Repositories\MyClassRepo;
use App\Repositories\UserRepo;
use App\Services\BikramSambatService;
use App\Services\LibraryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class BookController extends Controller
{
    protected $bookRepo;
    protected $libraryService;
    protected $myClass;
    protected $userRepo;
    protected $bsService;

    public function __construct(
        BookRepo $bookRepo,
        LibraryService $libraryService,
        MyClassRepo $myClass,
        UserRepo $userRepo,
        BikramSambatService $bsService
    ) {
        $this->middleware('auth');
        $this->middleware('teamLibrary', [
            'only' => [
                'create', 'store', 'edit', 'update', 'destroy',
                'circulations', 'issue', 'returnBook',
                'categories', 'storeCategory', 'updateCategory', 'destroyCategory'
            ]
        ]);

        $this->bookRepo = $bookRepo;
        $this->libraryService = $libraryService;
        $this->myClass = $myClass;
        $this->userRepo = $userRepo;
        $this->bsService = $bsService;
    }

    /**
     * Display catalogue or library dashboard.
     */
    public function index(Request $req)
    {
        $d['search'] = $search = $req->get('search');
        $d['category_id'] = $categoryId = $req->get('category_id');
        $d['my_class_id'] = $classId = $req->get('my_class_id');

        $d['books'] = $this->bookRepo->search($search, $categoryId, $classId);
        $d['categories'] = $this->bookRepo->allCategories();
        $d['my_classes'] = $this->myClass->all();

        if (Qs::userIsTeamLibrary()) {
            $d['stats'] = $this->bookRepo->getStatistics();
            $d['overdue_loans'] = $this->bookRepo->getOverdueLoans();
            $d['pending_requests'] = $this->bookRepo->getPendingRequests();
            $d['students'] = \App\Models\StudentRecord::with('user')->where('grad', 0)->get()->sortBy('user.name');
            $d['staff'] = \App\User::whereIn('user_type', Qs::getStaff(['student', 'parent']))->orderBy('name', 'asc')->get();

            // Default dates in BS and AD
            $todayAd = Carbon::today()->toDateString();
            $d['default_issue_date_ad'] = $todayAd;
            $d['default_due_date_ad'] = Carbon::today()->addDays(14)->toDateString();
            $d['default_issue_date_bs'] = $this->bsService->adToBs($todayAd)['bs_date'] ?? $todayAd;
            $d['default_due_date_bs'] = $this->bsService->adToBs($d['default_due_date_ad'])['bs_date'] ?? $d['default_due_date_ad'];

            return view('pages.support_team.books.index', $d);
        }

        // Students / Parents / Teachers view library catalogue
        $d['my_requests'] = $this->bookRepo->getUserRequests(Auth::id());
        return view('pages.support_team.books.catalogue', $d);
    }

    /**
     * Show book details.
     */
    public function show($id)
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return redirect()->route('books.index')->with('flash_danger', __('msg.rnf'));
        }

        $d['book'] = $book;
        $d['categories'] = $this->bookRepo->allCategories();
        $d['active_loans'] = $book->activeLoans()->with('user')->get();

        return view('pages.support_team.books.show', $d);
    }

    /**
     * Show form to create new book.
     */
    public function create()
    {
        $d['categories'] = $this->bookRepo->allCategories();
        $d['my_classes'] = $this->myClass->all();
        return view('pages.support_team.books.create', $d);
    }

    /**
     * Store newly created book in library.
     */
    public function store(BookCreateRequest $req)
    {
        $data = $req->except(['_token', 'cover_image']);
        $data['issued_copies'] = 0;

        if ($req->hasFile('cover_image')) {
            $image = $req->file('cover_image');
            $filename = 'book_' . time() . '_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/uploads/books', $filename);
            $data['cover_image'] = 'uploads/books/' . $filename;
        }

        $this->bookRepo->create($data);

        return redirect()->route('books.index')->with('flash_success', __('Record Created Successfully'));
    }

    /**
     * Show edit form for a book.
     */
    public function edit($id)
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return redirect()->route('books.index')->with('flash_danger', __('msg.rnf'));
        }

        $d['book'] = $book;
        $d['categories'] = $this->bookRepo->allCategories();
        $d['my_classes'] = $this->myClass->all();

        return view('pages.support_team.books.edit', $d);
    }

    /**
     * Update book details.
     */
    public function update(BookUpdateRequest $req, $id)
    {
        $book = $this->bookRepo->find($id);
        if (!$book) {
            return redirect()->route('books.index')->with('flash_danger', __('msg.rnf'));
        }

        $data = $req->except(['_token', '_method', 'cover_image']);

        if ($req->hasFile('cover_image')) {
            $image = $req->file('cover_image');
            $filename = 'book_' . time() . '_' . Str::random(8) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('public/uploads/books', $filename);
            $data['cover_image'] = 'uploads/books/' . $filename;
        }

        $this->bookRepo->update($id, $data);

        return redirect()->route('books.index')->with('flash_success', __('Record Updated Successfully'));
    }

    /**
     * Delete or archive book.
     */
    public function destroy($id)
    {
        try {
            $this->bookRepo->delete($id);
            return back()->with('flash_success', __('Record Deleted Successfully'));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book deletion failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'book_id' => $id,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while deleting the book. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Circulation Management page: active loans, history, and overdue tracking.
     */
    public function circulations(Request $req)
    {
        $d['active_loans'] = $this->bookRepo->getActiveLoans();
        $d['overdue_loans'] = $this->bookRepo->getOverdueLoans();
        $d['returned_loans'] = $this->bookRepo->getReturnedHistory();
        $d['books'] = $this->bookRepo->all();
        $d['borrowers'] = \App\User::whereIn('user_type', ['student', 'teacher', 'admin', 'super_admin', 'accountant', 'librarian'])->orderBy('name', 'asc')->get();

        $todayAd = Carbon::today()->toDateString();
        $d['today_ad'] = $todayAd;
        $d['today_bs'] = $this->bsService->adToBs($todayAd)['bs_date'] ?? $todayAd;
        $defaultDueAd = Carbon::today()->addDays(14)->toDateString();
        $d['default_due_bs'] = $this->bsService->adToBs($defaultDueAd)['bs_date'] ?? $defaultDueAd;

        return view('pages.support_team.books.circulations', $d);
    }

    /**
     * Display library catalogue for all users.
     */
    public function catalogue(Request $req)
    {
        return $this->index($req);
    }

    /**
     * Issue a book to a student or staff member.
     */
    public function issue(BookIssueRequest $req)
    {
        try {
            $issueDate = $req->issue_date ?: $req->issue_date_bs ?: Carbon::today()->toDateString();
            $dueDate = $req->due_date ?: $req->due_date_bs ?: Carbon::today()->addDays(14)->toDateString();

            // Handle potential Bikram Sambat date strings
            if ($req->issue_date_bs || (str_contains($issueDate, '-') && !Carbon::hasFormat($issueDate, 'Y-m-d'))) {
                $issueConv = $this->bsService->bsToAd($issueDate);
                $issueDate = $issueConv['ad_date'] ?? $issueDate;
            }
            if ($req->due_date_bs || (str_contains($dueDate, '-') && !Carbon::hasFormat($dueDate, 'Y-m-d'))) {
                $dueConv = $this->bsService->bsToAd($dueDate);
                $dueDate = $dueConv['ad_date'] ?? $dueDate;
            }

            $this->libraryService->issueBook(
                $req->book_id,
                $req->user_id,
                $issueDate,
                $dueDate,
                $req->notes,
                $req->request_id
            );

            return back()->with('flash_success', __('Book issued successfully.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book issue failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'book_id' => $req->book_id,
                'user_id' => $req->user_id,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while issuing the book. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Record the return of an issued book.
     */
    public function returnBook(Request $req, $id)
    {
        $req->validate([
            'return_date' => 'nullable|string',
            'return_date_bs' => 'nullable|string',
            'fine_amount' => 'nullable|numeric|min:0',
            'fine_paid' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $returnDate = $req->return_date ?: $req->return_date_bs ?: Carbon::today()->toDateString();
            if ($req->return_date_bs || (str_contains($returnDate, '-') && !Carbon::hasFormat($returnDate, 'Y-m-d'))) {
                $retConv = $this->bsService->bsToAd($returnDate);
                $returnDate = $retConv['ad_date'] ?? $returnDate;
            }

            $this->libraryService->returnBook(
                (int) $id,
                $returnDate,
                (float) ($req->fine_amount ?? 0),
                (bool) ($req->fine_paid ?? false),
                $req->notes
            );

            return back()->with('flash_success', __('Book returned successfully and inventory updated.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book return failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'loan_id' => $id,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while returning the book. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Categories management page.
     */
    public function categories()
    {
        $d['categories'] = $this->bookRepo->allCategories();
        return view('pages.support_team.books.categories', $d);
    }

    public function storeCategory(Request $req)
    {
        $req->validate([
            'name' => 'required|string|max:100|unique:book_categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $this->bookRepo->createCategory($req->only(['name', 'description']));

        return back()->with('flash_success', __('Record Created Successfully'));
    }

    public function updateCategory(Request $req, $id)
    {
        $req->validate([
            'name' => 'required|string|max:100|unique:book_categories,name,' . $id,
            'description' => 'nullable|string|max:500',
        ]);

        $this->bookRepo->updateCategory($id, $req->only(['name', 'description']));

        return back()->with('flash_success', __('Record Updated Successfully'));
    }

    public function destroyCategory($id)
    {
        try {
            $this->bookRepo->deleteCategory($id);
            return back()->with('flash_success', __('Record Deleted Successfully'));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Category deletion failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'category_id' => $id,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while deleting the category. Reference ID: :id', ['id' => $correlationId]));
        }
    }
}
