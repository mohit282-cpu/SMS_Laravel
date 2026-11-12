<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNepalLocationToUsersTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'province_id')) {
                    $table->unsignedInteger('province_id')->nullable()->index()->after('lga_id');
                }
                if (!Schema::hasColumn('users', 'district_id')) {
                    $table->unsignedInteger('district_id')->nullable()->index()->after('province_id');
                }
                if (!Schema::hasColumn('users', 'local_level_id')) {
                    $table->unsignedInteger('local_level_id')->nullable()->index()->after('district_id');
                }
                if (!Schema::hasColumn('users', 'ward_no')) {
                    $table->unsignedSmallInteger('ward_no')->nullable()->after('local_level_id');
                }
                if (!Schema::hasColumn('users', 'tole')) {
                    $table->string('tole', 150)->nullable()->after('ward_no');
                }
                if (!Schema::hasColumn('users', 'street')) {
                    $table->string('street', 191)->nullable()->after('tole');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $cols = array_filter(
                    ['province_id', 'district_id', 'local_level_id', 'ward_no', 'tole', 'street'],
                    fn ($col) => Schema::hasColumn('users', $col)
                );
                if (!empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
}

