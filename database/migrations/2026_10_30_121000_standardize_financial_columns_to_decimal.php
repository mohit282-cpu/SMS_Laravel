<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class StandardizeFinancialColumnsToDecimal extends Migration
{
    /**
     * Run the migrations.
     * Safely alters legacy integer monetary columns to DECIMAL(12,2) for exact currency and paisa handling.
     *
     * @return void
     */
    public function up()
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            // Safe non-destructive modification of monetary fields to DECIMAL(12,2)
            DB::statement("ALTER TABLE payments MODIFY amount DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE payment_records MODIFY amt_paid DECIMAL(12,2) NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE payment_records MODIFY balance DECIMAL(12,2) NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE payment_records MODIFY discount_amt DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE receipts MODIFY amt_paid DECIMAL(12,2) NOT NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE receipts MODIFY balance DECIMAL(12,2) NOT NULL DEFAULT 0.00");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->decimal('amount', 12, 2)->default(0.00)->change();
            });

            Schema::table('payment_records', function (Blueprint $table) {
                $table->decimal('amt_paid', 12, 2)->nullable()->default(0.00)->change();
                $table->decimal('balance', 12, 2)->nullable()->default(0.00)->change();
                $table->decimal('discount_amt', 12, 2)->default(0.00)->change();
            });

            Schema::table('receipts', function (Blueprint $table) {
                $table->decimal('amt_paid', 12, 2)->default(0.00)->change();
                $table->decimal('balance', 12, 2)->default(0.00)->change();
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
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE payments MODIFY amount INT NOT NULL");
            DB::statement("ALTER TABLE payment_records MODIFY amt_paid INT NULL");
            DB::statement("ALTER TABLE payment_records MODIFY balance INT NULL");
            DB::statement("ALTER TABLE payment_records MODIFY discount_amt DECIMAL(10,2) NOT NULL DEFAULT 0.00");
            DB::statement("ALTER TABLE receipts MODIFY amt_paid INT NOT NULL");
            DB::statement("ALTER TABLE receipts MODIFY balance INT NOT NULL");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->integer('amount')->change();
            });

            Schema::table('payment_records', function (Blueprint $table) {
                $table->integer('amt_paid')->nullable()->change();
                $table->integer('balance')->nullable()->change();
                $table->decimal('discount_amt', 10, 2)->default(0.00)->change();
            });

            Schema::table('receipts', function (Blueprint $table) {
                $table->integer('amt_paid')->change();
                $table->integer('balance')->change();
            });
        }
    }
}
