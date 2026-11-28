<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancePayrollAndInventoryTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('finance_ledgers') && !Schema::hasColumn('finance_ledgers', 'voucher_type')) {
            Schema::table('finance_ledgers', function (Blueprint $table) {
                $table->string('voucher_type', 40)->nullable()->after('entry_type');
                $table->string('party_name', 150)->nullable()->after('reference_no');
            });
        }

        if (!Schema::hasTable('staff_payrolls')) {
            Schema::create('staff_payrolls', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('fiscal_year_id')->nullable();
                $table->string('salary_month_bs', 30)->index();
                $table->decimal('basic_salary', 12, 2)->default(0);
                $table->decimal('allowances', 12, 2)->default(0);
                $table->decimal('deductions', 12, 2)->default(0);
                $table->decimal('provident_fund', 12, 2)->default(0);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('net_salary', 12, 2)->default(0);
                $table->enum('payment_status', ['pending', 'paid'])->default('pending')->index();
                $table->date('paid_date_ad')->nullable();
                $table->string('paid_date_bs', 20)->nullable();
                $table->string('payment_method', 50)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('processed_by')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->onDelete('set null');
                $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['user_id', 'salary_month_bs']);
            });
        }

        if (!Schema::hasTable('fee_concessions')) {
            Schema::create('fee_concessions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->string('academic_year', 40)->index();
                $table->string('title', 150);
                $table->enum('concession_type', ['fixed', 'percentage'])->default('fixed');
                $table->decimal('amount_or_percent', 10, 2);
                $table->text('reason')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('approved_by')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('inventory_categories')) {
            Schema::create('inventory_categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 120)->unique();
                $table->text('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('inventory_items')) {
            Schema::create('inventory_items', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('inventory_category_id')->nullable();
                $table->string('item_code', 50)->unique();
                $table->string('name', 150);
                $table->string('unit', 40)->default('pcs');
                $table->integer('current_stock')->default(0);
                $table->integer('reorder_level')->default(5);
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->string('location', 120)->nullable();
                $table->timestamps();

                $table->foreign('inventory_category_id')->references('id')->on('inventory_categories')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('inventory_transactions')) {
            Schema::create('inventory_transactions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('inventory_item_id');
                $table->enum('transaction_type', ['purchase', 'issue', 'return', 'adjustment'])->default('purchase');
                $table->integer('quantity');
                $table->decimal('unit_cost', 12, 2)->default(0);
                $table->string('vendor_or_recipient', 150)->nullable();
                $table->date('transaction_date_ad')->index();
                $table->string('transaction_date_bs', 20)->nullable();
                $table->string('reference_no', 80)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
                $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('inventory_categories');
        Schema::dropIfExists('fee_concessions');
        Schema::dropIfExists('staff_payrolls');

        if (Schema::hasTable('finance_ledgers') && Schema::hasColumn('finance_ledgers', 'voucher_type')) {
            Schema::table('finance_ledgers', function (Blueprint $table) {
                $table->dropColumn(['voucher_type', 'party_name']);
            });
        }
    }
}
