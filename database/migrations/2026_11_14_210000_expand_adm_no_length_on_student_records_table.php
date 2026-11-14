<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ExpandAdmNoLengthOnStudentRecordsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('student_records') && Schema::hasColumn('student_records', 'adm_no')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('ALTER TABLE `student_records` MODIFY `adm_no` VARCHAR(100) NULL');
            } else {
                try {
                    Schema::table('student_records', function (Blueprint $table) {
                        $table->string('adm_no', 100)->nullable()->change();
                    });
                } catch (\Throwable $e) {
                    // Fallback for drivers where column alteration requires doctrine/dbal
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
        if (Schema::hasTable('student_records') && Schema::hasColumn('student_records', 'adm_no')) {
            $driver = Schema::getConnection()->getDriverName();
            if ($driver === 'mysql') {
                $hasLongValues = DB::table('student_records')->whereRaw('CHAR_LENGTH(`adm_no`) > 30')->exists();
                if (!$hasLongValues) {
                    DB::statement('ALTER TABLE `student_records` MODIFY `adm_no` VARCHAR(30) NULL');
                }
            }
        }
    }
}

