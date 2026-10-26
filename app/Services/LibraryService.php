<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BookRequest;
use App\Repositories\BookRepo;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LibraryService
{
    protected $bookRepo;
    protected $bsService;

    public function __construct(BookRepo $bookRepo, BikramSambatService $bsService)
    {
        $this->bookRepo = $bookRepo;
        $this->bsService = $bsService;
    }

    /**
     * Atomically issue a book to a student or staff member.
     *
     * @param int $bookId
     * @param int $userId
     * @param string|null $issueDateAd YYYY-MM-DD
     * @param string|null $dueDateAd YYYY-MM-DD
     * @param string|null $notes
     * @param int|null $bookRequestId Optional linked book request
     * @return BookRequest
     * @throws ValidationException
     */
    public function issueBook(int $bookId, int $userId, $issueDateAd = null, $dueDateAd = null, $notes = null, $bookRequestId = null): BookRequest
    {
        if ($issueDateAd && (int)substr((string)$issueDateAd, 0, 4) >= 2070) {
            $conv = $this->bsService->bsToAd($issueDateAd);
            $issueDateAd = $conv['ad_date'] ?? $issueDateAd;
        }
        if ($dueDateAd && (int)substr((string)$dueDateAd, 0, 4) >= 2070) {
            $conv = $this->bsService->bsToAd($dueDateAd);
            $dueDateAd = $conv['ad_date'] ?? $dueDateAd;
        }

        $issueDateAd = $issueDateAd ? Carbon::parse($issueDateAd)->toDateString() : Carbon::today()->toDateString();
        $dueDateAd = $dueDateAd ? Carbon::parse($dueDateAd)->toDateString() : Carbon::today()->addDays(14)->toDateString();

        if (Carbon::parse($dueDateAd)->lt(Carbon::parse($issueDateAd))) {
            throw ValidationException::withMessages([
                'due_date' => [__('Due date cannot be earlier than the issue date.')],
            ]);
        }

        // Validate user exists and is eligible (student or staff)
        $borrower = User::find($userId);
        if (!$borrower) {
            throw ValidationException::withMessages([
                'user_id' => [__('Borrower user account not found.')],
            ]);
        }

        return DB::transaction(function () use ($bookId, $userId, $borrower, $issueDateAd, $dueDateAd, $notes, $bookRequestId) {
            // Lock the book row to prevent race conditions during concurrent issues
            $book = Book::where('id', $bookId)->lockForUpdate()->first();
            if (!$book) {
                throw ValidationException::withMessages([
                    'book_id' => [__('Book not found.')],
                ]);
            }

            $availableCopies = max(0, ((int)$book->total_copies) - ((int)($book->issued_copies ?? 0)));
            if ($availableCopies <= 0) {
                throw ValidationException::withMessages([
                    'book_id' => [__('No copies of this book are currently available for issue.')],
                ]);
            }

            // Convert AD dates to BS representation for dual display
            $issueDateBs = $this->bsService->adToBs($issueDateAd)['bs_date'] ?? null;
            $dueDateBs = $this->bsService->adToBs($dueDateAd)['bs_date'] ?? null;

            // Atomically increment issued copies
            $book->increment('issued_copies');

            $data = [
                'book_id' => $book->id,
                'user_id' => $userId,
                'borrower_type' => $borrower->user_type,
                'request_date' => $issueDateBs ?: $issueDateAd,
                'issue_date' => $issueDateAd,
                'due_date' => $dueDateAd,
                'start_date' => $issueDateAd,
                'end_date' => $dueDateAd,
                'returned' => '0',
                'status' => 'issued',
                'approved_by' => Auth::id(),
                'issued_by' => Auth::id(),
                'notes' => $notes,
            ];

            if ($bookRequestId) {
                $loan = BookRequest::where('id', $bookRequestId)->lockForUpdate()->first();
                if ($loan) {
                    $loan->update($data);
                    return $loan;
                }
            }

            return BookRequest::create($data);
        });
    }

    /**
     * Atomically record return of an issued book.
     *
     * @param int $loanId
     * @param string|null $returnDateAd YYYY-MM-DD
     * @param float $fineAmount
     * @param bool $finePaid
     * @param string|null $notes
     * @return BookRequest
     * @throws ValidationException
     */
    public function returnBook(int $loanId, $returnDateAd = null, float $fineAmount = 0.00, bool $finePaid = false, $notes = null): BookRequest
    {
        if ($returnDateAd && (int)substr((string)$returnDateAd, 0, 4) >= 2070) {
            $conv = $this->bsService->bsToAd($returnDateAd);
            $returnDateAd = $conv['ad_date'] ?? $returnDateAd;
        }

        $returnDateAd = $returnDateAd ? Carbon::parse($returnDateAd)->toDateString() : Carbon::today()->toDateString();

        return DB::transaction(function () use ($loanId, $returnDateAd, $fineAmount, $finePaid, $notes) {
            $loan = BookRequest::where('id', $loanId)->lockForUpdate()->first();
            if (!$loan) {
                throw ValidationException::withMessages([
                    'loan_id' => [__('Circulation record not found.')],
                ]);
            }

            if ($loan->status === 'returned' || $loan->returned === '1') {
                throw ValidationException::withMessages([
                    'loan_id' => [__('This book copy has already been returned.')],
                ]);
            }

            // Lock book row and decrement issued_copies safely
            $book = Book::where('id', $loan->book_id)->lockForUpdate()->first();
            if ($book) {
                $currentIssued = (int)($book->issued_copies ?? 0);
                $book->update([
                    'issued_copies' => max(0, $currentIssued - 1),
                ]);
            }

            $loan->update([
                'return_date' => $returnDateAd,
                'returned' => '1',
                'status' => 'returned',
                'returned_by_staff' => Auth::id(),
                'fine_amount' => max(0, $fineAmount),
                'fine_paid' => $finePaid,
                'notes' => $notes ? ($loan->notes ? $loan->notes . "\nReturn Note: " . $notes : $notes) : $loan->notes,
            ]);

            return $loan;
        });
    }

    /**
     * Submit a student or staff book request.
     */
    public function submitRequest(int $bookId, int $userId, $notes = null): BookRequest
    {
        $book = Book::findOrFail($bookId);
        $user = User::findOrFail($userId);

        // Check if user already has an active pending or issued request for this book
        $existing = BookRequest::where('book_id', $bookId)
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'approved', 'issued'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'book_id' => [__('You already have an active request or loan for this book.')],
            ]);
        }

        $todayAd = Carbon::today()->toDateString();
        $todayBs = $this->bsService->adToBs($todayAd)['bs_date'] ?? $todayAd;

        return BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'borrower_type' => $user->user_type,
            'request_date' => $todayBs,
            'start_date' => $todayAd,
            'end_date' => Carbon::today()->addDays(14)->toDateString(),
            'returned' => '0',
            'status' => 'pending',
            'notes' => $notes,
        ]);
    }

    /**
     * Approve a pending book request.
     */
    public function approveRequest(int $requestId, $notes = null): BookRequest
    {
        $loan = BookRequest::findOrFail($requestId);
        if ($loan->status !== 'pending') {
            throw new \RuntimeException(__('Only pending requests can be approved.'));
        }

        $loan->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'notes' => $notes ? ($loan->notes ? $loan->notes . "\nApproval Note: " . $notes : $notes) : $loan->notes,
        ]);

        return $loan;
    }

    /**
     * Reject a pending book request.
     */
    public function rejectRequest(int $requestId, $notes = null): BookRequest
    {
        $loan = BookRequest::findOrFail($requestId);
        if (!in_array($loan->status, ['pending', 'approved'])) {
            throw new \RuntimeException(__('This request cannot be rejected in its current status.'));
        }

        $loan->update([
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'notes' => $notes ? ($loan->notes ? $loan->notes . "\nRejection Note: " . $notes : $notes) : $loan->notes,
        ]);

        return $loan;
    }

    /**
     * Cancel own pending request.
     */
    public function cancelRequest(int $requestId, int $userId): BookRequest
    {
        $loan = BookRequest::where('id', $requestId)->where('user_id', $userId)->firstOrFail();
        if ($loan->status !== 'pending') {
            throw new \RuntimeException(__('Only pending requests can be cancelled.'));
        }

        $loan->update([
            'status' => 'cancelled',
        ]);

        return $loan;
    }
}
