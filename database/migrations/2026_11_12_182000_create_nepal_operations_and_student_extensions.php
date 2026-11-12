<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNepalOperationsAndStudentExtensions extends Migration
{
    public function up()
    {
        if (Schema::hasTable('student_records') && !Schema::hasColumn('student_records', 'name_ne')) {
            Schema::table('student_records', function (Blueprint $table) {
                $table->string('name_ne', 191)->nullable()->after('adm_no');
                $table->string('dob_bs', 10)->nullable()->after('name_ne');
                $table->string('admission_date_bs', 10)->nullable()->after('dob_bs');
                $table->string('father_name', 150)->nullable()->after('admission_date_bs');
                $table->string('mother_name', 150)->nullable()->after('father_name');
                $table->string('municipality', 150)->nullable()->after('mother_name');
                $table->unsignedSmallInteger('ward_no')->nullable()->after('municipality');
                $table->string('scholarship_category', 80)->nullable()->after('ward_no');
                $table->string('previous_school', 191)->nullable()->after('scholarship_category');
                $table->enum('enrollment_status', ['active', 'transferred', 'withdrawn', 'graduated'])->default('active')->after('previous_school');
            });
        }

        if (!Schema::hasTable('finance_ledgers')) {
            Schema::create('finance_ledgers', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('fiscal_year_id')->nullable();
                $table->string('entry_date_bs', 10)->index();
                $table->date('entry_date_ad')->index();
                $table->enum('entry_type', ['income', 'expense'])->index();
                $table->string('category', 100)->index();
                $table->enum('account_head', ['cash', 'bank'])->default('cash');
                $table->decimal('amount', 12, 2);
                $table->string('reference_no', 80)->nullable();
                $table->text('description')->nullable();
                $table->unsignedInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->foreign('fiscal_year_id')->references('id')->on('fiscal_years')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('transport_routes')) {
            Schema::create('transport_routes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->string('name_ne', 180)->nullable();
                $table->string('vehicle_number', 50)->nullable();
                $table->string('driver_name', 120)->nullable();
                $table->string('driver_phone', 30)->nullable();
                $table->decimal('monthly_fee', 10, 2)->default(0.00);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('student_transports')) {
            Schema::create('student_transports', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->unsignedInteger('transport_route_id');
                $table->string('pickup_point', 150)->nullable();
                $table->string('academic_year', 40)->index();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('transport_route_id')->references('id')->on('transport_routes')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('student_certificates')) {
            Schema::create('student_certificates', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->enum('certificate_type', ['character', 'transfer', 'bonafide', 'study', 'leaving']);
                $table->string('certificate_number', 60)->unique();
                $table->string('verification_code', 64)->unique();
                $table->string('issued_date_bs', 10);
                $table->date('issued_date_ad');
                $table->unsignedInteger('issued_by')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('student_certificates');
        Schema::dropIfExists('student_transports');
        Schema::dropIfExists('transport_routes');
        Schema::dropIfExists('finance_ledgers');

        if (Schema::hasTable('student_records') && Schema::hasColumn('student_records', 'name_ne')) {
            Schema::table('student_records', function (Blueprint $table) {
                $table->dropColumn([
                    'name_ne', 'dob_bs', 'admission_date_bs', 'father_name', 'mother_name',
                    'municipality', 'ward_no', 'scholarship_category', 'previous_school',
                    'enrollment_status',
                ]);
            });
        }
    }
}
