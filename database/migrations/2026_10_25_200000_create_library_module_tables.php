<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateLibraryModuleTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Ensure 'librarian' user type exists in user_types table
        if (Schema::hasTable('user_types')) {
            $librarianType = DB::table('user_types')->where('title', 'librarian')->first();
            if (!$librarianType) {
                DB::table('user_types')->insert([
                    'title' => 'librarian',
                    'name' => 'Librarian',
                    'level' => 6,
                ]);
            }
        }

        // 2. Create book_categories table if not exists
        if (!Schema::hasTable('book_categories')) {
            Schema::create('book_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->unique();
                $table->string('slug', 120)->nullable();
                $table->text('description')->nullable();
                $table->timestamps();
            });

            // Seed initial categories
            $categories = [
                ['name' => 'Course Textbooks', 'slug' => 'course-textbooks', 'description' => 'Prescribed school curriculum textbooks'],
                ['name' => 'Reference & Encyclopedia', 'slug' => 'reference-encyclopedia', 'description' => 'Dictionaries, encyclopedias and academic reference'],
                ['name' => 'Science & Mathematics', 'slug' => 'science-mathematics', 'description' => 'Science, physics, chemistry, biology and math literature'],
                ['name' => 'Nepali Literature', 'slug' => 'nepali-literature', 'description' => 'Nepali novels, poems, epics and stories'],
                ['name' => 'English Literature & Fiction', 'slug' => 'english-literature-fiction', 'description' => 'Fiction, stories, classic and modern novels in English'],
                ['name' => 'History, Culture & Geography', 'slug' => 'history-culture-geography', 'description' => 'Nepal and world history, geography, and social studies'],
                ['name' => 'General Knowledge & Periodicals', 'slug' => 'general-knowledge-periodicals', 'description' => 'Magazines, journals, trivia and competitive exam material'],
            ];

            foreach ($categories as $cat) {
                DB::table('book_categories')->insert(array_merge($cat, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }

        // 3. Extend books table with required library fields
        if (Schema::hasTable('books')) {
            Schema::table('books', function (Blueprint $table) {
                if (!Schema::hasColumn('books', 'category_id')) {
                    $table->unsignedInteger('category_id')->nullable()->after('my_class_id');
                    $table->foreign('category_id')->references('id')->on('book_categories')->onDelete('set null');
                }
                if (!Schema::hasColumn('books', 'isbn')) {
                    $table->string('isbn', 50)->nullable()->unique()->after('name');
                }
                if (!Schema::hasColumn('books', 'publisher')) {
                    $table->string('publisher', 150)->nullable()->after('author');
                }
                if (!Schema::hasColumn('books', 'edition')) {
                    $table->string('edition', 50)->nullable()->after('publisher');
                }
                if (!Schema::hasColumn('books', 'publication_year')) {
                    $table->string('publication_year', 20)->nullable()->after('edition');
                }
                if (!Schema::hasColumn('books', 'language')) {
                    $table->string('language', 50)->default('English')->after('publication_year');
                }
                if (!Schema::hasColumn('books', 'cover_image')) {
                    $table->string('cover_image', 255)->nullable()->after('location');
                }
                if (!Schema::hasColumn('books', 'deleted_at')) {
                    $table->softDeletes()->after('updated_at');
                }
            });
        }

        // 4. Extend book_requests table for complete circulation lifecycle
        if (Schema::hasTable('book_requests')) {
            Schema::table('book_requests', function (Blueprint $table) {
                if (!Schema::hasColumn('book_requests', 'borrower_type')) {
                    $table->string('borrower_type', 30)->default('student')->after('user_id');
                }
                if (!Schema::hasColumn('book_requests', 'request_date')) {
                    $table->string('request_date', 30)->nullable()->after('borrower_type');
                }
                if (!Schema::hasColumn('book_requests', 'issue_date')) {
                    $table->string('issue_date', 30)->nullable()->after('request_date');
                }
                if (!Schema::hasColumn('book_requests', 'due_date')) {
                    $table->string('due_date', 30)->nullable()->after('issue_date');
                }
                if (!Schema::hasColumn('book_requests', 'return_date')) {
                    $table->string('return_date', 30)->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('book_requests', 'approved_by')) {
                    $table->unsignedInteger('approved_by')->nullable()->after('returned');
                    $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('book_requests', 'issued_by')) {
                    $table->unsignedInteger('issued_by')->nullable()->after('approved_by');
                    $table->foreign('issued_by')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('book_requests', 'returned_by_staff')) {
                    $table->unsignedInteger('returned_by_staff')->nullable()->after('issued_by');
                    $table->foreign('returned_by_staff')->references('id')->on('users')->onDelete('set null');
                }
                if (!Schema::hasColumn('book_requests', 'fine_amount')) {
                    $table->decimal('fine_amount', 8, 2)->default(0.00)->after('returned_by_staff');
                }
                if (!Schema::hasColumn('book_requests', 'fine_paid')) {
                    $table->boolean('fine_paid')->default(false)->after('fine_amount');
                }
                if (!Schema::hasColumn('book_requests', 'notes')) {
                    $table->text('notes')->nullable()->after('fine_paid');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('book_requests')) {
            Schema::table('book_requests', function (Blueprint $table) {
                // Foreign keys must be dropped before their columns, otherwise
                // MySQL/MariaDB refuses with "Cannot drop index ... needed in a
                // foreign key constraint" and rollbacks become impossible.
                foreach (['approved_by', 'issued_by', 'returned_by_staff'] as $foreignColumn) {
                    if (Schema::hasColumn('book_requests', $foreignColumn)) {
                        $table->dropForeign([$foreignColumn]);
                    }
                }

                $columns = ['borrower_type', 'request_date', 'issue_date', 'due_date', 'return_date', 'approved_by', 'issued_by', 'returned_by_staff', 'fine_amount', 'fine_paid', 'notes'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('book_requests', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        if (Schema::hasTable('books')) {
            Schema::table('books', function (Blueprint $table) {
                if (Schema::hasColumn('books', 'category_id')) {
                    $table->dropForeign(['category_id']);
                    $table->dropColumn('category_id');
                }
                $columns = ['isbn', 'publisher', 'edition', 'publication_year', 'language', 'cover_image', 'deleted_at'];
                foreach ($columns as $col) {
                    if (Schema::hasColumn('books', $col)) {
                        $table->dropColumn($col);
                    }
                }
            });
        }

        Schema::dropIfExists('book_categories');
    }
}
