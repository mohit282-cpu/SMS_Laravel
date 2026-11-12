<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNepalFoundationTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('nepal_provinces')) {
            Schema::create('nepal_provinces', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100);
                $table->string('name_ne', 120);
                $table->string('code', 20)->unique();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('nepal_districts')) {
            Schema::create('nepal_districts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('province_id');
                $table->string('name', 100);
                $table->string('name_ne', 120);
                $table->string('code', 20)->nullable();
                $table->timestamps();

                $table->foreign('province_id')->references('id')->on('nepal_provinces')->onDelete('cascade');
                $table->unique(['province_id', 'name']);
            });
        }

        if (!Schema::hasTable('nepal_local_levels')) {
            Schema::create('nepal_local_levels', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('district_id');
                $table->string('name', 150);
                $table->string('name_ne', 180)->nullable();
                $table->enum('level_type', ['metropolitan', 'sub_metropolitan', 'municipality', 'rural_municipality'])->default('municipality');
                $table->unsignedTinyInteger('total_wards')->default(1);
                $table->timestamps();

                $table->foreign('district_id')->references('id')->on('nepal_districts')->onDelete('cascade');
                $table->index(['district_id', 'level_type']);
            });
        }

        if (!Schema::hasTable('school_profiles')) {
            Schema::create('school_profiles', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 191);
                $table->string('name_ne', 191)->nullable();
                $table->string('emis_code', 50)->nullable()->index();
                $table->enum('school_type', ['community', 'institutional', 'montessori', 'religious', 'technical', 'other'])->default('institutional');
                $table->enum('boarding_type', ['day', 'boarding', 'both'])->default('day');
                $table->unsignedInteger('province_id')->nullable();
                $table->unsignedInteger('district_id')->nullable();
                $table->unsignedInteger('local_level_id')->nullable();
                $table->string('local_level_manual', 150)->nullable();
                $table->unsignedSmallInteger('ward_no')->nullable();
                $table->string('tole', 150)->nullable();
                $table->string('pan_number', 30)->nullable();
                $table->string('vat_number', 30)->nullable();
                $table->string('established_bs', 10)->nullable();
                $table->date('established_ad')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('website', 191)->nullable();
                $table->enum('calendar_preference', ['bs', 'ad', 'both'])->default('both');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('province_id')->references('id')->on('nepal_provinces')->onDelete('set null');
                $table->foreign('district_id')->references('id')->on('nepal_districts')->onDelete('set null');
                $table->foreign('local_level_id')->references('id')->on('nepal_local_levels')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('fiscal_years')) {
            Schema::create('fiscal_years', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 50)->unique();
                $table->string('start_date_bs', 10);
                $table->string('end_date_bs', 10);
                $table->date('start_date_ad')->nullable();
                $table->date('end_date_ad')->nullable();
                $table->boolean('is_active')->default(false)->index();
                $table->boolean('is_closed')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        $tables = ['fiscal_years', 'school_profiles', 'nepal_local_levels', 'nepal_districts', 'nepal_provinces'];
        foreach ($tables as $table) {
            if (Schema::hasTable($table) && \Illuminate\Support\Facades\DB::table($table)->count() > 0) {
                throw new \RuntimeException("Refusing to drop populated table '{$table}' during rollback.");
            }
        }

        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
}

