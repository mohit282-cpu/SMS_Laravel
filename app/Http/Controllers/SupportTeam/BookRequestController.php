<?php

namespace App\Http\Controllers\SupportTeam;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\BookRequest;
use App\Repositories\BookRepo;
use App\Services\LibraryService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookRequestController extends Controller
{
    protected $bookRepo;
    protected $libraryService;

    public function __construct(BookRepo $bookRepo, LibraryService $libraryService)
    {
        $this->middleware('auth');
        $this->middleware('teamLibrary', ['only' => ['approve', 'reject', 'issueFromRequest']]);
        $this->bookRepo = $bookRepo;
        $this->libraryService = $libraryService;
    }

    /**
     * Display list of book requests.
     */
    public function index(Request $req)
    {
        if (Qs::userIsTeamLibrary()) {
            $all = $this->bookRepo->allRequests();
            $d['pending_requests'] = $all->where('status', 'pending');
            $d['approved_requests'] = $all->where('status', 'approved');
            $d['past_requests'] = $all->whereIn('status', ['rejected', 'cancelled', 'issued']);
            $d['stats'] = [
                'pending' => $d['pending_requests']->count(),
                'approved' => $d['approved_requests']->count(),
                'rejected' => $all->whereIn('status', ['rejected', 'cancelled'])->count(),
            ];
            return view('pages.support_team.books.requests', $d);
        }

        return $this->myRequests();
    }

    /**
     * Display student/staff personal requests and loans.
     */
    public function myRequests()
    {
        $d['my_requests'] = $this->bookRepo->getUserRequests(Auth::id());
        $d['my_loans'] = BookRequest::where('user_id', Auth::id())
            ->where('status', 'issued')
            ->with(['book.category'])
            ->orderBy('due_date', 'asc')
            ->get();

        return view('pages.support_team.books.my_requests', $d);
    }

    /**
     * Store a newly created book request from student or staff.
     */
    public function store(Request $req)
    {
        $req->validate([
            'book_id' => 'required|exists:books,id',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $this->libraryService->submitRequest((int) $req->book_id, Auth::id(), $req->notes);
            return back()->with('flash_success', __('Book request submitted successfully.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->withInput()->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\PDOException | \Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request submission failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected database error occurred. Reference ID: :id', ['id' => $correlationId]));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request submission failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'user_id' => Auth::id(),
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while processing your request. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Approve a pending book request.
     */
    public function approve(Request $req, $id = null)
    {
        $params = $req->route() ? $req->route()->parameters() : [];
        $targetId = $id ?: ($params['id'] ?? null) ?: $req->get('id');
        try {
            $bookRequest = BookRequest::find((int) $targetId);
            $this->libraryService->approveRequest((int) $targetId, $req->get('notes'));

            try {
                if ($bookRequest && $bookRequest->user_id) {
                    \App\Jobs\SendWebPushNotification::dispatch(
                        $bookRequest->user_id,
                        __('Library Update'),
                        __('Your book request has been approved.'),
                        route('book_requests.my_requests')
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WebPush notification dispatch failed for book approval: ' . $e->getMessage());
            }

            return back()->with('flash_success', __('Book request approved.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\PDOException | \Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request approval database failure [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected database error occurred. Reference ID: :id', ['id' => $correlationId]));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request approval failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while approving the request. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Reject a book request.
     */
    public function reject(Request $req, $id = null)
    {
        $params = $req->route() ? $req->route()->parameters() : [];
        $targetId = $id ?: ($params['id'] ?? null) ?: $req->get('id');
        try {
            $bookRequest = BookRequest::find((int) $targetId);
            $this->libraryService->rejectRequest((int) $targetId, $req->get('notes'));

            try {
                if ($bookRequest && $bookRequest->user_id) {
                    \App\Jobs\SendWebPushNotification::dispatch(
                        $bookRequest->user_id,
                        __('Library Update'),
                        __('Your book request status has been updated.'),
                        route('book_requests.my_requests')
                    );
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('WebPush notification dispatch failed for book rejection: ' . $e->getMessage());
            }

            return back()->with('flash_success', __('Book request rejected.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\PDOException | \Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request rejection database failure [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected database error occurred. Reference ID: :id', ['id' => $correlationId]));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request rejection failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while rejecting the request. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Cancel own pending request.
     */
    public function cancel(Request $req, $id = null)
    {
        $params = $req->route() ? $req->route()->parameters() : [];
        $targetId = $id ?: ($params['id'] ?? null) ?: $req->get('id');
        try {
            $this->libraryService->cancelRequest((int) $targetId, Auth::id());
            return back()->with('flash_success', __('Your book request has been cancelled.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\PDOException | \Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request cancellation database failure [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected database error occurred. Reference ID: :id', ['id' => $correlationId]));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book request cancellation failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while cancelling the request. Reference ID: :id', ['id' => $correlationId]));
        }
    }

    /**
     * Issue book directly from an approved request.
     */
    public function issueFromRequest(Request $req, $id = null)
    {
        $params = $req->route() ? $req->route()->parameters() : [];
        $targetId = $id ?: ($params['id'] ?? null) ?: $req->get('id');
        $loan = $this->bookRepo->findRequest((int) $targetId);
        if (!$loan) {
            return back()->with('flash_danger', __('msg.rnf'));
        }

        $req->validate([
            'due_date' => 'nullable|string',
            'issue_date' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $issueDate = $req->issue_date ?: Carbon::today()->toDateString();
            $dueDate = $req->due_date ?: Carbon::today()->addDays(14)->toDateString();

            $this->libraryService->issueBook(
                $loan->book_id,
                $loan->user_id,
                $issueDate,
                $dueDate,
                $req->notes,
                $loan->id
            );

            return back()->with('flash_success', __('Book issued from request successfully.'));
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->errors())->with('flash_danger', collect($e->errors())->flatten()->first());
        } catch (\PDOException | \Illuminate\Database\QueryException $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book issue from request database failure [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected database error occurred. Reference ID: :id', ['id' => $correlationId]));
        } catch (\RuntimeException $e) {
            return back()->with('flash_danger', $e->getMessage());
        } catch (\Throwable $e) {
            $correlationId = (string) \Illuminate\Support\Str::uuid();
            \Illuminate\Support\Facades\Log::error("Book issue from request failed [Ref: {$correlationId}]: " . $e->getMessage(), [
                'correlation_id' => $correlationId,
                'target_id' => $targetId,
                'exception' => $e,
            ]);
            return back()->with('flash_danger', __('An unexpected error occurred while issuing the book. Reference ID: :id', ['id' => $correlationId]));
        }
    }
}
