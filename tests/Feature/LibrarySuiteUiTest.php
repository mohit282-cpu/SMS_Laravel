<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookCategory;
use App\Models\BookRequest;
use App\User;
use Tests\TestCase;

class LibrarySuiteUiTest extends TestCase
{
    protected function getSuperAdmin()
    {
        return User::where('user_type', 'super_admin')->first();
    }

    protected function getLibrarian()
    {
        return User::where('user_type', 'librarian')->first() ?? $this->getSuperAdmin();
    }

    protected function getStudent()
    {
        return User::where('user_type', 'student')->first();
    }

    /**
     * Test 1: Library Dashboard & Inventory loads with executive header and 6 KPI stat cards.
     */
    public function test_library_dashboard_and_inventory_page_loads_with_executive_header_and_kpis()
    {
        $librarian = $this->getLibrarian();

        $response = $this->actingAs($librarian)->get(route('books.index'));

        $response->assertStatus(200);
        $response->assertSee('Library Dashboard &amp; Inventory Management', false);
        $response->assertSee('Total Titles');
        $response->assertSee('Total Copies');
        $response->assertSee('Available');
        $response->assertSee('Active Loans');
        $response->assertSee('Overdue');
        $response->assertSee('Requests');
        $response->assertSee('Book Catalogue');
        $response->assertSee('Add New Book');
    }

    /**
     * Test 2: Library Circulations page loads with executive header, 4 KPI cards and modals.
     */
    public function test_library_circulations_page_loads_with_executive_header_and_kpis()
    {
        $librarian = $this->getLibrarian();

        $response = $this->actingAs($librarian)->get(route('books.circulations'));

        $response->assertStatus(200);
        $response->assertSee('Library Circulations: Issue &amp; Return', false);
        $response->assertSee('Active Loans');
        $response->assertSee('Overdue Loans');
        $response->assertSee('Returned History');
        $response->assertSee('Available Books');
        $response->assertSee('modal-issue-book');
        $response->assertSee('modal-return-book');
    }

    /**
     * Test 3: Manage Book Requests page loads with executive header, 4 KPI cards and request queue.
     */
    public function test_book_requests_page_loads_with_executive_header_and_kpis()
    {
        $librarian = $this->getLibrarian();

        $response = $this->actingAs($librarian)->get(route('book_requests.index'));

        $response->assertStatus(200);
        $response->assertSee('Library Book Requests &amp; Reservations', false);
        $response->assertSee('Pending Requests');
        $response->assertSee('Approved (Ready)');
        $response->assertSee('Rejected / Cancelled');
        $response->assertSee('Total Inquiries');
        $response->assertSee('Pending Requests');
        $response->assertSee('Approved (Awaiting Collection)');
    }

    /**
     * Test 4: Manage Book Categories page loads with executive header, 4 KPI cards, directory and modal.
     */
    public function test_book_categories_page_loads_with_executive_header_and_kpis()
    {
        $librarian = $this->getLibrarian();

        $response = $this->actingAs($librarian)->get(route('books.categories'));

        $response->assertStatus(200);
        $response->assertSee('Manage Book Categories &amp; Library Classification', false);
        $response->assertSee('Total Categories');
        $response->assertSee('Categorized Books');
        $response->assertSee('In-Use Genres');
        $response->assertSee('Unused Categories');
        $response->assertSee('Category Directory');
        $response->assertSee('Add New Category');
        $response->assertSee('editCategoryModal');
    }

    /**
     * Test 5: Guest is redirected to login from library management endpoints.
     */
    public function test_guest_redirected_to_login_on_all_library_pages()
    {
        $this->get(route('books.index'))->assertRedirect(route('login'));
        $this->get(route('books.circulations'))->assertRedirect(route('login'));
        $this->get(route('book_requests.index'))->assertRedirect(route('login'));
        $this->get(route('books.categories'))->assertRedirect(route('login'));
    }
}
