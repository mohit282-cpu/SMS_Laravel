<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddDualDateAndMonthlyFeeColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Add calendar_display to users
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'calendar_display')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('calendar_display', 10)->default('default')->after('locale');
            });
        }

        // 2. Seed calendar_display setting
        if (Schema::hasTable('settings')) {
            $exists = DB::table('settings')->where('type', 'calendar_display')->exists();
            if (!$exists) {
                DB::table('settings')->insert([
                    'type' => 'calendar_display',
                    'description' => 'both',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // 3. Add monthly billing & dual date columns to payments
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!Schema::hasColumn('payments', 'period_type')) {
                    $table->string('period_type', 20)->default('one_time')->after('method');
                }
                if (!Schema::hasColumn('payments', 'fee_category')) {
                    $table->string('fee_category', 50)->default('tuition')->after('period_type');
                }
                if (!Schema::hasColumn('payments', 'bs_year')) {
                    $table->unsignedSmallInteger('bs_year')->nullable()->after('year');
                }
                if (!Schema::hasColumn('payments', 'bs_month')) {
                    $table->unsignedTinyInteger('bs_month')->nullable()->after('bs_year');
                }
                if (!Schema::hasColumn('payments', 'due_date')) {
                    $table->date('due_date')->nullable()->after('bs_month');
                }
                if (!Schema::hasColumn('payments', 'due_date_bs')) {
                    $table->string('due_date_bs', 10)->nullable()->after('due_date');
                }

                $table->index(['bs_year', 'bs_month'], 'payments_bs_period_index');
            });
        }

        // 4. Add monthly billing & dual date columns to payment_records
        if (Schema::hasTable('payment_records')) {
            Schema::table('payment_records', function (Blueprint $table) {
                if (!Schema::hasColumn('payment_records', 'bs_year')) {
                    $table->unsignedSmallInteger('bs_year')->nullable()->after('year');
                }
                if (!Schema::hasColumn('payment_records', 'bs_month')) {
                    $table->unsignedTinyInteger('bs_month')->nullable()->after('bs_year');
                }
                if (!Schema::hasColumn('payment_records', 'due_date')) {
                    $table->date('due_date')->nullable()->after('bs_month');
                }
                if (!Schema::hasColumn('payment_records', 'due_date_bs')) {
                    $table->string('due_date_bs', 10)->nullable()->after('due_date');
                }
                if (!Schema::hasColumn('payment_records', 'discount_amt')) {
                    $table->decimal('discount_amt', 10, 2)->default(0.00)->after('balance');
                }
                if (!Schema::hasColumn('payment_records', 'waiver_reason')) {
                    $table->string('waiver_reason', 255)->nullable()->after('discount_amt');
                }

                $table->index(['bs_year', 'bs_month'], 'payment_records_bs_period_index');
            });

            // Add unique index on (payment_id, student_id) if no duplicates exist
            $duplicates = DB::table('payment_records')
                ->select('payment_id', 'student_id', DB::raw('count(*) as count'))
                ->groupBy('payment_id', 'student_id')
                ->having('count', '>', 1)
                ->count();

            if ($duplicates === 0) {
                // Check if index already exists
                $indexes = DB::select("SHOW INDEX FROM payment_records WHERE Key_name = 'payment_records_pay_student_unique'");
                if (empty($indexes)) {
                    Schema::table('payment_records', function (Blueprint $table) {
                        $table->unique(['payment_id', 'student_id'], 'payment_records_pay_student_unique');
                    });
                }
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
        if (Schema::hasTable('payment_records')) {
            $indexes = DB::select("SHOW INDEX FROM payment_records WHERE Key_name = 'payment_records_pay_student_unique'");
            if (!empty($indexes)) {
                // Ensure standalone index on payment_id exists so foreign key is satisfied before dropping unique index
                $hasFkIndex = DB::select("SHOW INDEX FROM payment_records WHERE Key_name = 'payment_records_payment_id_foreign'");
                if (empty($hasFkIndex)) {
                    Schema::table('payment_records', function (Blueprint $table) {
                        $table->index('payment_id', 'payment_records_payment_id_foreign');
                    });
                }

                Schema::table('payment_records', function (Blueprint $table) {
                    $table->dropUnique('payment_records_pay_student_unique');
                });
            }

            Schema::table('payment_records', function (Blueprint $table) {
                $periodIdx = DB::select("SHOW INDEX FROM payment_records WHERE Key_name = 'payment_records_bs_period_index'");
                if (!empty($periodIdx)) {
                    $table->dropIndex('payment_records_bs_period_index');
                }

                if (Schema::hasColumn('payment_records', 'waiver_reason')) {
                    $table->dropColumn('waiver_reason');
                }
                if (Schema::hasColumn('payment_records', 'discount_amt')) {
                    $table->dropColumn('discount_amt');
                }
                if (Schema::hasColumn('payment_records', 'due_date_bs')) {
                    $table->dropColumn('due_date_bs');
                }
                if (Schema::hasColumn('payment_records', 'due_date')) {
                    $table->dropColumn('due_date');
                }
                if (Schema::hasColumn('payment_records', 'bs_month')) {
                    $table->dropColumn('bs_month');
                }
                if (Schema::hasColumn('payment_records', 'bs_year')) {
                    $table->dropColumn('bs_year');
                }
            });
        }

        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                $periodIdx = DB::select("SHOW INDEX FROM payments WHERE Key_name = 'payments_bs_period_index'");
                if (!empty($periodIdx)) {
                    $table->dropIndex('payments_bs_period_index');
                }

                if (Schema::hasColumn('payments', 'due_date_bs')) {
                    $table->dropColumn('due_date_bs');
                }
                if (Schema::hasColumn('payments', 'due_date')) {
                    $table->dropColumn('due_date');
                }
                if (Schema::hasColumn('payments', 'bs_month')) {
                    $table->dropColumn('bs_month');
                }
                if (Schema::hasColumn('payments', 'bs_year')) {
                    $table->dropColumn('bs_year');
                }
                if (Schema::hasColumn('payments', 'fee_category')) {
                    $table->dropColumn('fee_category');
                }
                if (Schema::hasColumn('payments', 'period_type')) {
                    $table->dropColumn('period_type');
                }
            });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')->where('type', 'calendar_display')->delete();
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'calendar_display')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('calendar_display');
            });
        }
    }
}
