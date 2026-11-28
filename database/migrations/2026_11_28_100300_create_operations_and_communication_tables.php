<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOperationsAndCommunicationTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('transport_attendances')) {
            Schema::create('transport_attendances', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('transport_route_id');
                $table->unsignedInteger('student_record_id');
                $table->date('attendance_date_ad')->index();
                $table->string('attendance_date_bs', 20)->nullable();
                $table->enum('trip_type', ['pickup', 'drop'])->default('pickup');
                $table->enum('status', ['boarded', 'missed', 'absent'])->default('boarded');
                $table->string('remarks', 191)->nullable();
                $table->unsignedInteger('marked_by')->nullable();
                $table->timestamps();

                $table->foreign('transport_route_id')->references('id')->on('transport_routes')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['transport_route_id', 'student_record_id', 'attendance_date_ad', 'trip_type'], 'tr_att_unique');
            });
        }

        if (!Schema::hasTable('hostel_allocations')) {
            Schema::create('hostel_allocations', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->unsignedInteger('dorm_id');
                $table->string('room_no', 50);
                $table->string('bed_no', 50)->nullable();
                $table->decimal('monthly_fee', 10, 2)->default(0);
                $table->string('meal_plan', 80)->nullable();
                $table->string('assigned_date_bs', 20)->nullable();
                $table->enum('status', ['active', 'vacated'])->default('active');
                $table->text('remarks')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('dorm_id')->references('id')->on('dorms')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('canteen_lunch_logs')) {
            Schema::create('canteen_lunch_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('student_record_id');
                $table->date('log_date_ad')->index();
                $table->string('log_date_bs', 20)->nullable();
                $table->enum('meal_type', ['lunch', 'snacks', 'breakfast'])->default('lunch');
                $table->enum('status', ['taken', 'absent'])->default('taken');
                $table->decimal('unit_charge', 8, 2)->default(0);
                $table->string('remarks', 191)->nullable();
                $table->unsignedInteger('marked_by')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('marked_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('visitor_logs')) {
            Schema::create('visitor_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->string('visitor_name', 150);
                $table->string('phone', 40)->nullable();
                $table->enum('visitor_type', ['parent', 'vendor', 'guest', 'official', 'other'])->default('parent');
                $table->unsignedInteger('student_record_id')->nullable();
                $table->string('person_to_meet', 150)->nullable();
                $table->string('purpose', 255);
                $table->string('id_card_no', 80)->nullable();
                $table->date('visit_date_ad')->index();
                $table->string('visit_date_bs', 20)->nullable();
                $table->string('check_in_time', 20);
                $table->string('check_out_time', 20)->nullable();
                $table->text('remarks')->nullable();
                $table->unsignedInteger('recorded_by')->nullable();
                $table->timestamps();

                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('set null');
                $table->foreign('recorded_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('complaints_feedback')) {
            Schema::create('complaints_feedback', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('submitted_by');
                $table->unsignedInteger('student_record_id')->nullable();
                $table->enum('entry_type', ['complaint', 'feedback', 'suggestion', 'appreciation', 'discipline_case'])->default('feedback');
                $table->string('subject', 191);
                $table->text('description');
                $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
                $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open')->index();
                $table->text('resolution_notes')->nullable();
                $table->unsignedInteger('handled_by')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->foreign('submitted_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('set null');
                $table->foreign('handled_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('portal_messages')) {
            Schema::create('portal_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sender_id');
                $table->unsignedInteger('recipient_id');
                $table->unsignedInteger('student_record_id')->nullable();
                $table->string('subject', 191);
                $table->text('body');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->foreign('sender_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('recipient_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('surveys')) {
            Schema::create('surveys', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('created_by');
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->string('target_role', 40)->default('all');
                $table->string('question_1', 255);
                $table->string('question_2', 255)->nullable();
                $table->string('question_3', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('closes_at')->nullable();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('survey_responses')) {
            Schema::create('survey_responses', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('survey_id');
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('rating')->default(5);
                $table->text('answer_1')->nullable();
                $table->text('answer_2')->nullable();
                $table->text('answer_3')->nullable();
                $table->text('comments')->nullable();
                $table->timestamps();

                $table->foreign('survey_id')->references('id')->on('surveys')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->unique(['survey_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('notification_logs')) {
            Schema::create('notification_logs', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('sender_id')->nullable();
                $table->unsignedInteger('recipient_user_id')->nullable();
                $table->enum('channel', ['in_app', 'web_push', 'sms_queued', 'email_queued'])->default('in_app');
                $table->string('category', 60)->default('general');
                $table->string('title', 191);
                $table->text('message');
                $table->enum('status', ['sent', 'queued', 'failed'])->default('sent');
                $table->string('target_role', 40)->nullable();
                $table->timestamps();

                $table->foreign('sender_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('recipient_user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('survey_responses');
        Schema::dropIfExists('surveys');
        Schema::dropIfExists('portal_messages');
        Schema::dropIfExists('complaints_feedback');
        Schema::dropIfExists('visitor_logs');
        Schema::dropIfExists('canteen_lunch_logs');
        Schema::dropIfExists('hostel_allocations');
        Schema::dropIfExists('transport_attendances');
    }
}
