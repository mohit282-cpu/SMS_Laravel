<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\BookRequest;
use App\Services\LibraryService;
use App\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ExceptionHandlingSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_raw_exception_is_not_exposed_to_user_on_unexpected_failure()
    {
        $student = User::where('user_type', 'student')->first();
        $book = Book::first();

        // Mock LibraryService to throw an unexpected database-level exception with sensitive SQL/schema
        $mockService = $this->createMock(LibraryService::class);
        $mockService->method('submitRequest')
            ->willThrowException(new \PDOException('SQLSTATE[42S02]: Base table or view not found: 1146 Table secret_db.users does not exist'));

        $this->app->instance(LibraryService::class, $mockService);

        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Book request submission failed')
                    && isset($context['correlation_id'])
                    && isset($context['exception']);
            });

        $response = $this->actingAs($student)->post(route('book_requests.store'), [
            'book_id' => $book->id,
            'notes' => 'Test notes',
        ]);

        $response->assertSessionHas('flash_danger');
        $flashDanger = session('flash_danger');

        // Verify sensitive SQL/table names are NEVER leaked to the session flash
        $this->assertStringNotContainsString('SQLSTATE', $flashDanger);
        $this->assertStringNotContainsString('secret_db', $flashDanger);
        $this->assertStringNotContainsString('Base table or view not found', $flashDanger);

        // Verify a reference ID is provided to the user
        $this->assertStringContainsString('Reference ID:', $flashDanger);
    }

    public function test_expected_validation_messages_remain_understandable()
    {
        $student = User::where('user_type', 'student')->first();

        $response = $this->actingAs($student)->post(route('book_requests.store'), [
            // Missing required book_id
        ]);

        $response->assertSessionHasErrors('book_id');
    }
}
