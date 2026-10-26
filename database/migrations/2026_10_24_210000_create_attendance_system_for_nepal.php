<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttendanceSystemForNepal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // 1. Student Attendance Table
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('student_record_id');
            $table->unsignedInteger('user_id'); // student user id
            $table->unsignedInteger('my_class_id');
            $table->unsignedInteger('section_id')->nullable();
            $table->unsignedInteger('academic_year_id')->nullable();
            $table->string('session', 50)->nullable();
            $table->date('attendance_date'); // Gregorian AD date
            $table->string('attendance_date_bs', 10); // Bikram Sambat date YYYY-MM-DD
            $table->string('status', 20)->default('present'); // present, absent, late, leave, half_day
            $table->string('remarks')->nullable();
            $table->unsignedInteger('recorded_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['student_record_id', 'attendance_date'], 'unique_student_date_att');
            $table->index(['my_class_id', 'section_id', 'attendance_date'], 'idx_class_sec_date');
            $table->index('attendance_date');
            $table->index('attendance_date_bs');

            $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
            $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Staff Attendance Table
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id'); // staff user id
            $table->unsignedInteger('staff_record_id')->nullable();
            $table->unsignedInteger('academic_year_id')->nullable();
            $table->string('session', 50)->nullable();
            $table->date('attendance_date'); // Gregorian AD date
            $table->string('attendance_date_bs', 10); // Bikram Sambat date YYYY-MM-DD
            $table->string('status', 20)->default('present'); // present, absent, late, leave, half_day
            $table->string('remarks')->nullable();
            $table->unsignedInteger('recorded_by')->nullable();
            $table->unsignedInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'attendance_date'], 'unique_staff_date_att');
            $table->index('attendance_date');
            $table->index('attendance_date_bs');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });

        // 3. Attendance Audit Trail Table
        Schema::create('attendance_audit_trails', function (Blueprint $table) {
            $table->increments('id');
            $table->string('attendance_type', 20); // 'student' or 'staff'
            $table->unsignedInteger('attendance_id');
            $table->unsignedInteger('user_id'); // student or staff user id
            $table->date('attendance_date');
            $table->string('attendance_date_bs', 10);
            $table->string('old_status', 20)->nullable();
            $table->string('new_status', 20);
            $table->text('old_remarks')->nullable();
            $table->text('new_remarks')->nullable();
            $table->string('action', 30)->default('create'); // 'create', 'update', 'delete', 'bulk_entry'
            $table->unsignedInteger('modified_by');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['attendance_type', 'attendance_id']);
            $table->index('attendance_date');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('modified_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_audit_trails');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('student_attendances');
    }
}
