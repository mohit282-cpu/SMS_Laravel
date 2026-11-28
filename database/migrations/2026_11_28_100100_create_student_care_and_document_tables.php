<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentCareAndDocumentTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('leave_requests')) {
            Schema::create('leave_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('student_record_id')->nullable();
                $table->enum('leave_scope', ['student', 'staff'])->default('student')->index();
                $table->enum('leave_type', ['sick', 'casual', 'urgent', 'annual', 'other'])->default('sick');
                $table->date('from_date_ad')->index();
                $table->string('from_date_bs', 20)->nullable();
                $table->date('to_date_ad')->index();
                $table->string('to_date_bs', 20)->nullable();
                $table->unsignedSmallInteger('total_days')->default(1);
                $table->text('reason');
                $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->index();
                $table->unsignedInteger('reviewed_by')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                $table->text('review_remarks')->nullable();
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('reviewed_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('student_wellness_logs')) {
            Schema::create('student_wellness_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->unsignedInteger('recorded_by');
                $table->enum('category', ['merit', 'demerit', 'infirmary', 'eca', 'health_check'])->default('merit')->index();
                $table->string('title', 191);
                $table->integer('points')->default(0);
                $table->text('description')->nullable();
                $table->date('log_date_ad')->index();
                $table->string('log_date_bs', 20)->nullable();
                $table->string('vitals_summary', 191)->nullable();
                $table->string('action_taken', 191)->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('recorded_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('student_groups')) {
            Schema::create('student_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150);
                $table->enum('category', ['house', 'club', 'bus', 'sports', 'academic', 'other'])->default('club');
                $table->text('description')->nullable();
                $table->unsignedInteger('incharge_user_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('incharge_user_id')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('student_group_members')) {
            Schema::create('student_group_members', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_group_id');
                $table->unsignedInteger('student_record_id');
                $table->string('role_in_group', 80)->nullable();
                $table->timestamps();

                $table->foreign('student_group_id')->references('id')->on('student_groups')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->unique(['student_group_id', 'student_record_id']);
            });
        }

        if (!Schema::hasTable('student_documents')) {
            Schema::create('student_documents', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->string('document_type', 80);
                $table->string('title', 191);
                $table->string('file_path');
                $table->string('original_name')->nullable();
                $table->unsignedInteger('uploaded_by')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('student_documents');
        Schema::dropIfExists('student_group_members');
        Schema::dropIfExists('student_groups');
        Schema::dropIfExists('student_wellness_logs');
        Schema::dropIfExists('leave_requests');
    }
}
