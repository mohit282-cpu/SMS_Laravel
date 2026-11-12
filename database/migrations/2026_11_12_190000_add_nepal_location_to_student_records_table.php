<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNepalLocationToStudentRecordsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_records')) {
            Schema::table('student_records', function (Blueprint $table) {
                if (!Schema::hasColumn('student_records', 'province_id')) {
                    $table->unsignedInteger('province_id')->nullable()->index()->after('mother_name');
                }
                if (!Schema::hasColumn('student_records', 'district_id')) {
                    $table->unsignedInteger('district_id')->nullable()->index()->after('province_id');
                }
                if (!Schema::hasColumn('student_records', 'local_level_id')) {
                    $table->unsignedInteger('local_level_id')->nullable()->index()->after('district_id');
                }
                if (!Schema::hasColumn('student_records', 'tole')) {
                    $table->string('tole', 150)->nullable()->after('ward_no');
                }
                if (!Schema::hasColumn('student_records', 'street')) {
                    $table->string('street', 191)->nullable()->after('tole');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('student_records')) {
            $cols = array_filter(
                ['province_id', 'district_id', 'local_level_id', 'tole', 'street'],
                fn ($col) => Schema::hasColumn('student_records', $col)
            );
            foreach ($cols as $col) {
                if (\Illuminate\Support\Facades\DB::table('student_records')->whereNotNull($col)->exists()) {
                    throw new \RuntimeException("Refusing to drop populated column 'student_records.{$col}' during rollback.");
                }
            }
            if (!empty($cols)) {
                Schema::table('student_records', function (Blueprint $table) use ($cols) {
                    $table->dropColumn($cols);
                });
            }
        }
    }
}

