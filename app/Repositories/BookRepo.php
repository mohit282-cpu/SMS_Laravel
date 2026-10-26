<?php

namespace App\Repositories;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BookRepo
{
    /**
     * Get all active (non-soft-deleted) books.
     */
    public function all()
    {
        return Book::with(['category', 'my_class'])->orderBy('name', 'asc')->get();
    }

    /**
     * Find book by ID with relationships.
     */
    public function find($id)
    {
        return Book::with(['category', 'my_class', 'requests.user'])->find($id);
    }

    /**
     * Create a new book.
     */
    public function create(array $data)
    {
        if (!isset($data['issued_copies'])) {
            $data['issued_copies'] = 0;
        }
        return Book::create($data);
    }

    /**
     * Update book details.
     */
    public function update($id, array $data)
    {
        $book = Book::findOrFail($id);
        $book->update($data);
        return $book;
    }

    /**
     * Delete / archive a book if no active loans.
     */
    public function delete($id)
    {
        $book = Book::findOrFail($id);
        if ($book->activeLoans()->count() > 0) {
            throw new \RuntimeException(__('Cannot delete book while copies are currently issued to borrowers.'));
        }
        return $book->delete();
    }

    /**
     * Search books with filters.
     */
    public function search($search = null, $categoryId = null, $classId = null)
    {
        $query = Book::with(['category', 'my_class']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('author', 'LIKE', "%{$search}%")
                  ->orWhere('isbn', 'LIKE', "%{$search}%")
                  ->orWhere('publisher', 'LIKE', "%{$search}%")
                  ->orWhere('location', 'LIKE', "%{$search}%");
            });
        }

        if (!empty($categoryId)) {
            $query->where('category_id', $categoryId);
        }

        if (!empty($classId)) {
            $query->where('my_class_id', $classId);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**************** BOOK CATEGORIES ****************/

    public function allCategories()
    {
        return BookCategory::withCount('books')->orderBy('name', 'asc')->get();
    }

    public function findCategory($id)
    {
        return BookCategory::with('books')->find($id);
    }

    public function createCategory(array $data)
    {
        return BookCategory::create($data);
    }

    public function updateCategory($id, array $data)
    {
        $category = BookCategory::findOrFail($id);
        $category->update($data);
        return $category;
    }

    public function deleteCategory($id)
    {
        $category = BookCategory::findOrFail($id);
        if ($category->books()->count() > 0) {
            throw new \RuntimeException(__('Cannot delete category that has books assigned to it.'));
        }
        return $category->delete();
    }

    /**************** BOOK REQUESTS & CIRCULATION ****************/

    public function allRequests($status = null)
    {
        $query = BookRequest::with(['book.category', 'user', 'approver', 'issuer', 'receiver'])
            ->orderBy('id', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function getUserRequests($userId, $status = null)
    {
        $query = BookRequest::with(['book.category', 'issuer', 'receiver'])
            ->where('user_id', $userId)
            ->orderBy('id', 'desc');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function findRequest($id)
    {
        return BookRequest::with(['book', 'user', 'approver', 'issuer', 'receiver'])->find($id);
    }

    public function createRequest(array $data)
    {
        return BookRequest::create($data);
    }

    public function updateRequest($id, array $data)
    {
        $request = BookRequest::findOrFail($id);
        $request->update($data);
        return $request;
    }

    public function getActiveLoans()
    {
        return BookRequest::with(['book', 'user', 'issuer'])
            ->where('status', 'issued')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getOverdueLoans()
    {
        $today = Carbon::today()->toDateString();
        return BookRequest::with(['book', 'user', 'issuer'])
            ->where('status', 'issued')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getPendingRequests()
    {
        return BookRequest::with(['book', 'user'])
            ->where('status', 'pending')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getReturnedHistory()
    {
        return BookRequest::with(['book', 'user', 'issuer', 'receiver'])
            ->where('status', 'returned')
            ->orderBy('updated_at', 'desc')
            ->get();
    }

    /**
     * Compute summary statistics for library dashboard.
     */
    public function getStatistics()
    {
        $totalTitles = Book::count();
        $totalCopies = (int) Book::sum('total_copies');
        $issuedCopies = (int) Book::sum('issued_copies');
        $availableCopies = max(0, $totalCopies - $issuedCopies);

        $today = Carbon::today()->toDateString();
        $overdueCount = BookRequest::where('status', 'issued')
            ->whereNotNull('due_date')
            ->where('due_date', '<', $today)
            ->count();

        $pendingCount = BookRequest::where('status', 'pending')->count();
        $activeLoansCount = BookRequest::where('status', 'issued')->count();

        return [
            'total_titles' => $totalTitles,
            'total_copies' => $totalCopies,
            'issued_copies' => $issuedCopies,
            'available_copies' => $availableCopies,
            'overdue_count' => $overdueCount,
            'pending_count' => $pendingCount,
            'active_loans' => $activeLoansCount,
        ];
    }
}
