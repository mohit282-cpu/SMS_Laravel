<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookRequest;
use App\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LibraryManagementTest extends TestCase
{
    protected function getSuperAdmin(): User
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getLibrarian(): User
    {
        return User::where('user_type', 'librarian')->first();
    }

    protected function getTeacher(): User
    {
        return User::where('user_type', 'teacher')->first();
    }

    protected function getStudent(): User
    {
        return User::where('user_type', 'student')->first();
    }

    protected function getParent(): User
    {
        return User::where('user_type', 'parent')->first();
    }

    // ==========================================
    // 1. AUTHORIZATION TESTS
    // ==========================================

    public function test_super_admin_can_access_library_dashboard_and_inventory()
    {
        $admin = $this->getSuperAdmin();
        $response = $this->actingAs($admin)->get(route('books.index'));
        $response->assertStatus(200);
        $response->assertSee('Manage Library Books');
    }

    public function test_librarian_can_access_library_features()
    {
        $librarian = $this->getLibrarian();
        $response = $this->actingAs($librarian)->get(route('books.index'));
        $response->assertStatus(200);
        $response->assertSee('Manage Library Books');

        $circulations = $this->actingAs($librarian)->get(route('books.circulations'));
        $circulations->assertStatus(200);

        $requests = $this->actingAs($librarian)->get(route('book_requests.index'));
        $requests->assertStatus(200);

        $categories = $this->actingAs($librarian)->get(route('books.categories'));
        $categories->assertStatus(200);
    }

    public function test_unauthorized_student_cannot_perform_librarian_writes()
    {
        $student = $this->getStudent();

        // Attempt to create book
        $response = $this->actingAs($student)->post(route('books.store'), [
            'name' => 'Unauthorized Book',
            'total_copies' => 5,
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));

        // Attempt to issue book
        $issueResponse = $this->actingAs($student)->post(route('books.issue_book'), [
            'book_id' => 1,
            'user_id' => $student->id,
        ]);
        $this->assertTrue(in_array($issueResponse->getStatusCode(), [403, 302]));

        // Attempt to create category
        $catResponse = $this->actingAs($student)->post(route('books.categories.store'), [
            'name' => 'Hacked Category',
        ]);
        $this->assertTrue(in_array($catResponse->getStatusCode(), [403, 302]));
    }

    public function test_unauthorized_teacher_cannot_perform_librarian_writes()
    {
        $teacher = $this->getTeacher();

        $response = $this->actingAs($teacher)->post(route('books.store'), [
            'name' => 'Teacher Unauthorized Book',
            'total_copies' => 3,
        ]);
        $this->assertTrue(in_array($response->getStatusCode(), [403, 302]));
    }

    public function test_librarian_cannot_access_unrelated_administrative_functions()
    {
        $librarian = $this->getLibrarian();

        // System Settings
        $settingsResponse = $this->actingAs($librarian)->get(route('settings'));
        $this->assertTrue(in_array($settingsResponse->getStatusCode(), [403, 302]));

        // Users Management
        $usersResponse = $this->actingAs($librarian)->get(route('users.index'));
        $this->assertTrue(in_array($usersResponse->getStatusCode(), [403, 302]));

        // Payments Management
        $paymentsResponse = $this->actingAs($librarian)->get(route('payments.index'));
        $this->assertTrue(in_array($paymentsResponse->getStatusCode(), [403, 302]));
    }

    public function test_students_and_parents_have_read_only_catalogue_access()
    {
        $student = $this->getStudent();
        $response = $this->actingAs($student)->get(route('books.catalogue'));
        $response->assertStatus(200);
        $response->assertSee('Library Catalogue');

        $parent = $this->getParent();
        $parentResponse = $this->actingAs($parent)->get(route('books.catalogue'));
        $parentResponse->assertStatus(200);
        $parentResponse->assertSee('Library Catalogue');
    }

    // ==========================================
    // 2. BOOK INVENTORY MANAGEMENT TESTS
    // ==========================================

    public function test_librarian_can_create_view_update_and_delete_book()
    {
        $librarian = $this->getLibrarian();
        $category = BookCategory::first();

        $testIsbn = '978-9937-' . rand(10000, 99999);

        // 1. Create Book
        $createResponse = $this->actingAs($librarian)->post(route('books.store'), [
            'name' => 'Nepali Sahitya Sangraha',
            'isbn' => $testIsbn,
            'category_id' => $category ? $category->id : null,
            'author' => 'Bhanubhakta Acharya',
            'publisher' => 'Sajha Prakashan',
            'edition' => '1st Edition',
            'publication_year' => '2024',
            'location' => 'Rack N-1',
            'total_copies' => 5,
            'description' => 'A classic collection of Nepali literature.',
        ]);
        $createResponse->assertSessionHas('flash_success');

        $book = Book::where('isbn', $testIsbn)->first();
        $this->assertNotNull($book);
        $this->assertEquals(5, $book->total_copies);
        $this->assertEquals(0, $book->issued_copies);
        $this->assertEquals(5, $book->available_copies);

        // 2. View Book Details
        $viewResponse = $this->actingAs($librarian)->get(route('books.show', $book->id));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('Nepali Sahitya Sangraha');

        // 3. Update Book
        $updateResponse = $this->actingAs($librarian)->put(route('books.update', $book->id), [
            'name' => 'Nepali Sahitya Sangraha (Revised)',
            'isbn' => $testIsbn,
            'total_copies' => 8,
            'author' => 'Bhanubhakta Acharya',
        ]);
        $updateResponse->assertSessionHas('flash_success');

        $book->refresh();
        $this->assertEquals('Nepali Sahitya Sangraha (Revised)', $book->name);
        $this->assertEquals(8, $book->total_copies);
        $this->assertEquals(8, $book->available_copies);

        // 4. Delete Book
        $deleteResponse = $this->actingAs($librarian)->delete(route('books.destroy', $book->id));
        $deleteResponse->assertSessionHas('flash_success');
        $this->assertSoftDeleted('books', ['id' => $book->id]);
    }

    public function test_duplicate_isbn_is_rejected()
    {
        $librarian = $this->getLibrarian();
        $existingIsbn = '978-9937-' . rand(100000, 999999);

        Book::create([
            'name' => 'First Book With ISBN',
            'isbn' => $existingIsbn,
            'total_copies' => 3,
            'issued_copies' => 0,
        ]);

        $response = $this->actingAs($librarian)->post(route('books.store'), [
            'name' => 'Duplicate ISBN Book',
            'isbn' => $existingIsbn,
            'total_copies' => 2,
        ]);
        $response->assertSessionHasErrors('isbn');
    }

    public function test_invalid_copy_counts_are_rejected()
    {
        $librarian = $this->getLibrarian();

        // 0 copies
        $response = $this->actingAs($librarian)->post(route('books.store'), [
            'name' => 'Zero Copies Book',
            'total_copies' => 0,
        ]);
        $response->assertSessionHasErrors('total_copies');

        // Negative copies
        $negativeResponse = $this->actingAs($librarian)->post(route('books.store'), [
            'name' => 'Negative Copies Book',
            'total_copies' => -4,
        ]);
        $negativeResponse->assertSessionHasErrors('total_copies');
    }

    public function test_book_with_active_loans_cannot_be_deleted()
    {
        $librarian = $this->getLibrarian();
        $student = $this->getStudent();

        $book = Book::create([
            'name' => 'Active Loan Protected Book',
            'total_copies' => 2,
            'issued_copies' => 1,
        ]);

        BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $student->id,
            'status' => 'issued',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        $deleteResponse = $this->actingAs($librarian)->delete(route('books.destroy', $book->id));
        $deleteResponse->assertSessionHas('flash_danger');

        // Ensure book was NOT deleted
        $this->assertDatabaseHas('books', ['id' => $book->id, 'deleted_at' => null]);
    }

    // ==========================================
    // 3. BOOK ISSUE AND RETURN WORKFLOW
    // ==========================================

    public function test_successful_issue_reduces_available_copies_and_return_restores_them()
    {
        $librarian = $this->getLibrarian();
        $student = $this->getStudent();

        $book = Book::create([
            'name' => 'Circulation Flow Book',
            'total_copies' => 3,
            'issued_copies' => 0,
        ]);

        $this->assertEquals(3, $book->available_copies);

        // 1. Issue Book
        $issueResponse = $this->actingAs($librarian)->post(route('books.issue_book'), [
            'book_id' => $book->id,
            'user_id' => $student->id,
            'issue_date_bs' => '2083-07-09',
            'due_date_bs' => '2083-07-23',
            'notes' => 'Testing issue flow',
        ]);
        $issueResponse->assertSessionHas('flash_success');

        $book->refresh();
        $this->assertEquals(1, $book->issued_copies);
        $this->assertEquals(2, $book->available_copies);

        $loan = BookRequest::where('book_id', $book->id)
            ->where('user_id', $student->id)
            ->where('status', 'issued')
            ->first();

        $this->assertNotNull($loan);
        $this->assertEquals('2083-07-09', $loan->issue_date_bs);
        $this->assertEquals('2083-07-23', $loan->due_date_bs);

        // 2. Return Book
        $returnResponse = $this->actingAs($librarian)->post(route('books.return_book', $loan->id), [
            'return_date_bs' => '2083-07-15',
            'fine_amount' => 0,
            'notes' => 'Returned on time in good condition',
        ]);
        $returnResponse->assertSessionHas('flash_success');

        $book->refresh();
        $this->assertEquals(0, $book->issued_copies);
        $this->assertEquals(3, $book->available_copies);

        $loan->refresh();
        $this->assertEquals('returned', $loan->status);
        $this->assertNotNull($loan->return_date);
    }

    public function test_issuing_with_zero_available_copies_fails()
    {
        $librarian = $this->getLibrarian();
        $student = $this->getStudent();

        $book = Book::create([
            'name' => 'Stock Exhausted Book',
            'total_copies' => 1,
            'issued_copies' => 1, // 0 available
        ]);

        $this->assertEquals(0, $book->available_copies);

        $response = $this->actingAs($librarian)->post(route('books.issue_book'), [
            'book_id' => $book->id,
            'user_id' => $student->id,
            'issue_date_bs' => '2083-07-09',
            'due_date_bs' => '2083-07-23',
        ]);

        $response->assertSessionHas('flash_danger');
        $book->refresh();
        $this->assertEquals(1, $book->issued_copies);
        $this->assertEquals(0, $book->available_copies);
    }

    public function test_duplicate_returns_do_not_increase_inventory_twice()
    {
        $librarian = $this->getLibrarian();
        $student = $this->getStudent();

        $book = Book::create([
            'name' => 'Duplicate Return Protection Book',
            'total_copies' => 2,
            'issued_copies' => 1,
        ]);

        $loan = BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $student->id,
            'status' => 'issued',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        // First return: valid
        $this->actingAs($librarian)->post(route('books.return_book', $loan->id));
        $book->refresh();
        $this->assertEquals(0, $book->issued_copies);
        $this->assertEquals(2, $book->available_copies);

        // Second return attempt: rejected
        $duplicateResponse = $this->actingAs($librarian)->post(route('books.return_book', $loan->id));
        $duplicateResponse->assertSessionHas('flash_danger');

        $book->refresh();
        // Issued copies must remain 0 and available must not become 3
        $this->assertEquals(0, $book->issued_copies);
        $this->assertEquals(2, $book->available_copies);
    }

    public function test_invalid_borrower_is_rejected()
    {
        $librarian = $this->getLibrarian();

        $book = Book::create([
            'name' => 'Invalid Borrower Book',
            'total_copies' => 2,
            'issued_copies' => 0,
        ]);

        $response = $this->actingAs($librarian)->post(route('books.issue_book'), [
            'book_id' => $book->id,
            'user_id' => 999999, // Non-existent user
        ]);

        $response->assertSessionHasErrors('user_id');
        $book->refresh();
        $this->assertEquals(0, $book->issued_copies);
    }

    // ==========================================
    // 4. BOOK REQUESTS AND APPROVAL WORKFLOW
    // ==========================================

    public function test_book_request_lifecycle_submit_approve_and_issue()
    {
        $student = $this->getStudent();
        $librarian = $this->getLibrarian();

        $book = Book::create([
            'name' => 'Request Flow Book',
            'total_copies' => 2,
            'issued_copies' => 0,
        ]);

        // 1. Student Submits Book Request
        $requestResponse = $this->actingAs($student)->post(route('book_requests.store'), [
            'book_id' => $book->id,
            'notes' => 'Need this book for annual exam revision',
        ]);
        $requestResponse->assertSessionHas('flash_success');

        $req = BookRequest::where('book_id', $book->id)
            ->where('user_id', $student->id)
            ->where('status', 'pending')
            ->first();

        $this->assertNotNull($req);

        // 2. Librarian Approves Request
        $approveResponse = $this->actingAs($librarian)->post(route('book_requests.approve', $req->id));
        $approveResponse->assertSessionHas('flash_success');

        $req->refresh();
        $this->assertEquals('approved', $req->status);
        $this->assertEquals($librarian->id, $req->approved_by);

        // 3. Issue Book from Approved Request
        $issueFromReqResponse = $this->actingAs($librarian)->post(route('book_requests.issue_from_request', $req->id));
        $issueFromReqResponse->assertSessionHas('flash_success');

        $req->refresh();
        $this->assertEquals('issued', $req->status);
        $this->assertEquals($librarian->id, $req->issued_by);

        // Ensure stock was decremented exactly once
        $book->refresh();
        $this->assertEquals(1, $book->issued_copies);
        $this->assertEquals(1, $book->available_copies);
    }

    public function test_librarian_can_reject_pending_request()
    {
        $student = $this->getStudent();
        $librarian = $this->getLibrarian();

        $book = Book::create([
            'name' => 'Reject Flow Book',
            'total_copies' => 1,
            'issued_copies' => 0,
        ]);

        $req = BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $student->id,
            'borrower_type' => 'student',
            'status' => 'pending',
            'request_date' => now()->toDateString(),
        ]);

        $rejectResponse = $this->actingAs($librarian)->post(route('book_requests.reject', $req->id));
        $rejectResponse->assertSessionHas('flash_success');

        $req->refresh();
        $this->assertEquals('rejected', $req->status);

        // Stock must remain intact
        $book->refresh();
        $this->assertEquals(0, $book->issued_copies);
        $this->assertEquals(1, $book->available_copies);
    }

    public function test_student_can_cancel_own_pending_request()
    {
        $student = $this->getStudent();

        $book = Book::create([
            'name' => 'Cancel Flow Book',
            'total_copies' => 1,
            'issued_copies' => 0,
        ]);

        $req = BookRequest::create([
            'book_id' => $book->id,
            'user_id' => $student->id,
            'borrower_type' => 'student',
            'status' => 'pending',
            'request_date' => now()->toDateString(),
        ]);

        $cancelResponse = $this->actingAs($student)->post(route('book_requests.cancel', $req->id));
        $cancelResponse->assertSessionHas('flash_success');

        $req->refresh();
        $this->assertEquals('cancelled', $req->status);
    }

    // ==========================================
    // 5. CATEGORY MANAGEMENT TESTS
    // ==========================================

    public function test_category_with_associated_books_cannot_be_deleted()
    {
        $librarian = $this->getLibrarian();

        $uniqueSuffix = uniqid();
        $category = BookCategory::create([
            'name' => 'Protected Category ' . $uniqueSuffix,
            'slug' => 'protected-category-' . $uniqueSuffix,
        ]);

        Book::create([
            'name' => 'Book in Protected Category',
            'category_id' => $category->id,
            'total_copies' => 1,
            'issued_copies' => 0,
        ]);

        $deleteResponse = $this->actingAs($librarian)->delete(route('books.categories.destroy', $category->id));
        $deleteResponse->assertSessionHas('flash_danger');

        $this->assertDatabaseHas('book_categories', ['id' => $category->id]);
    }
}
