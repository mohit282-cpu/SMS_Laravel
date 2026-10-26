<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MakeStartAndEndDateNullableOnBookRequests extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('book_requests')) {
            try {
                // Use raw alter statements to ensure compatibility across MySQL versions
                DB::statement("ALTER TABLE `book_requests` MODIFY COLUMN `start_date` VARCHAR(191) NULL DEFAULT NULL");
                DB::statement("ALTER TABLE `book_requests` MODIFY COLUMN `end_date` VARCHAR(191) NULL DEFAULT NULL");
            } catch (\Throwable $e) {
                // Fallback for SQLite / other drivers
                Schema::table('book_requests', function (Blueprint $table) {
                    $table->string('start_date')->nullable()->change();
                    $table->string('end_date')->nullable()->change();
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No-op for safety
    }
}
