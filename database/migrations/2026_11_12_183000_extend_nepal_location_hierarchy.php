<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExtendNepalLocationHierarchy extends Migration
{
    public function up()
    {
        if (Schema::hasTable('nepal_provinces') && !Schema::hasColumn('nepal_provinces', 'is_active')) {
            Schema::table('nepal_provinces', function (Blueprint $table) {
                $table->string('country_code', 5)->default('NP')->after('id');
                $table->boolean('is_active')->default(true)->after('code');
                $table->date('effective_date')->nullable()->after('is_active');
                $table->string('data_source', 191)->nullable()->after('effective_date');
            });
        }

        if (Schema::hasTable('nepal_districts') && !Schema::hasColumn('nepal_districts', 'is_active')) {
            Schema::table('nepal_districts', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('code');
                $table->date('effective_date')->nullable()->after('is_active');
                $table->string('data_source', 191)->nullable()->after('effective_date');
            });
        }

        if (Schema::hasTable('nepal_local_levels') && !Schema::hasColumn('nepal_local_levels', 'is_active')) {
            Schema::table('nepal_local_levels', function (Blueprint $table) {
                $table->string('code', 30)->nullable()->after('name_ne')->index();
                $table->boolean('is_active')->default(true)->after('total_wards');
                $table->date('effective_date')->nullable()->after('is_active');
                $table->string('data_source', 191)->nullable()->after('effective_date');
            });
        }

        if (!Schema::hasTable('nepal_wards')) {
            Schema::create('nepal_wards', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('local_level_id');
                $table->unsignedSmallInteger('ward_number');
                $table->string('name', 80)->nullable();
                $table->string('name_ne', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('local_level_id')->references('id')->on('nepal_local_levels')->onDelete('cascade');
                $table->unique(['local_level_id', 'ward_number']);
            });
        }

        if (!Schema::hasTable('nepal_settlements')) {
            Schema::create('nepal_settlements', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('local_level_id');
                $table->unsignedSmallInteger('ward_number')->nullable();
                $table->string('name', 150);
                $table->string('name_ne', 180)->nullable();
                $table->string('settlement_type', 50)->default('tole');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('local_level_id')->references('id')->on('nepal_local_levels')->onDelete('cascade');
                $table->index(['local_level_id', 'ward_number']);
            });
        }

        if (Schema::hasTable('school_profiles') && !Schema::hasColumn('school_profiles', 'landmark')) {
            Schema::table('school_profiles', function (Blueprint $table) {
                $table->string('street', 191)->nullable()->after('tole');
                $table->string('landmark', 191)->nullable()->after('street');
                $table->string('postal_address', 191)->nullable()->after('landmark');
                $table->decimal('latitude', 10, 7)->nullable()->after('postal_address');
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            });
        }
    }

    public function down()
    {
        foreach (['nepal_settlements', 'nepal_wards'] as $table) {
            if (Schema::hasTable($table) && \Illuminate\Support\Facades\DB::table($table)->count() > 0) {
                throw new \RuntimeException("Refusing to drop populated table '{$table}' during rollback.");
            }
        }

        if (Schema::hasTable('school_profiles') && Schema::hasColumn('school_profiles', 'landmark')) {
            Schema::table('school_profiles', function (Blueprint $table) {
                $table->dropColumn(['street', 'landmark', 'postal_address', 'latitude', 'longitude']);
            });
        }

        Schema::dropIfExists('nepal_settlements');
        Schema::dropIfExists('nepal_wards');

        foreach (['nepal_local_levels', 'nepal_districts', 'nepal_provinces'] as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'is_active')) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $cols = ['is_active', 'effective_date', 'data_source'];
                    if ($tableName === 'nepal_provinces') {
                        $cols[] = 'country_code';
                    }
                    if ($tableName === 'nepal_local_levels') {
                        $cols[] = 'code';
                    }
                    $table->dropColumn($cols);
                });
            }
        }
    }
}

