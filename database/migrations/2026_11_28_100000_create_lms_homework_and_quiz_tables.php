<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLmsHomeworkAndQuizTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('homeworks')) {
            Schema::create('homeworks', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('teacher_id');
                $table->unsignedInteger('my_class_id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->enum('homework_type', ['homework', 'assignment', 'daily_diary'])->default('homework');
                $table->string('attachment_path')->nullable();
                $table->string('reference_link', 500)->nullable();
                $table->date('assigned_date_ad')->index();
                $table->string('assigned_date_bs', 20)->nullable();
                $table->date('due_date_ad')->nullable()->index();
                $table->string('due_date_bs', 20)->nullable();
                $table->unsignedSmallInteger('max_marks')->default(10);
                $table->boolean('is_published')->default(true)->index();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('homework_submissions')) {
            Schema::create('homework_submissions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('homework_id');
                $table->unsignedInteger('student_record_id');
                $table->text('submission_text')->nullable();
                $table->string('attachment_path')->nullable();
                $table->enum('status', ['pending', 'submitted', 'evaluated', 'late', 'incomplete'])->default('submitted');
                $table->decimal('marks_obtained', 6, 2)->nullable();
                $table->text('teacher_remarks')->nullable();
                $table->unsignedInteger('evaluated_by')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamp('evaluated_at')->nullable();
                $table->timestamps();

                $table->foreign('homework_id')->references('id')->on('homeworks')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->foreign('evaluated_by')->references('id')->on('users')->onDelete('set null');
                $table->unique(['homework_id', 'student_record_id']);
            });
        }

        if (!Schema::hasTable('lesson_plans')) {
            Schema::create('lesson_plans', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('teacher_id');
                $table->unsignedInteger('my_class_id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('unit_title', 191);
                $table->string('lesson_topic', 191);
                $table->text('learning_objectives')->nullable();
                $table->text('teaching_aids')->nullable();
                $table->date('planned_date_ad')->index();
                $table->string('planned_date_bs', 20)->nullable();
                $table->date('completed_date_ad')->nullable();
                $table->string('completed_date_bs', 20)->nullable();
                $table->enum('completion_status', ['planned', 'in_progress', 'completed'])->default('planned');
                $table->text('daily_report_notes')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('online_classes')) {
            Schema::create('online_classes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('teacher_id');
                $table->unsignedInteger('my_class_id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('title', 191);
                $table->enum('platform', ['zoom', 'google_meet', 'ms_teams', 'jitsi', 'other'])->default('zoom');
                $table->string('meeting_url', 500);
                $table->string('meeting_code', 100)->nullable();
                $table->string('meeting_passcode', 60)->nullable();
                $table->date('scheduled_date_ad')->index();
                $table->string('scheduled_date_bs', 20)->nullable();
                $table->string('start_time', 20);
                $table->unsignedSmallInteger('duration_minutes')->default(45);
                $table->enum('status', ['scheduled', 'live', 'completed', 'cancelled'])->default('scheduled');
                $table->text('description')->nullable();
                $table->timestamps();

                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('online_quizzes')) {
            Schema::create('online_quizzes', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('created_by');
                $table->unsignedInteger('my_class_id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('title', 191);
                $table->text('description')->nullable();
                $table->unsignedSmallInteger('duration_minutes')->default(30);
                $table->unsignedSmallInteger('pass_marks')->default(40);
                $table->date('scheduled_date_ad')->nullable();
                $table->string('scheduled_date_bs', 20)->nullable();
                $table->boolean('is_published')->default(false)->index();
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('set null');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('quiz_questions')) {
            Schema::create('quiz_questions', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('online_quiz_id');
                $table->text('question_text');
                $table->string('option_a', 500);
                $table->string('option_b', 500);
                $table->string('option_c', 500)->nullable();
                $table->string('option_d', 500)->nullable();
                $table->enum('correct_option', ['a', 'b', 'c', 'd']);
                $table->unsignedSmallInteger('marks')->default(1);
                $table->text('explanation')->nullable();
                $table->timestamps();

                $table->foreign('online_quiz_id')->references('id')->on('online_quizzes')->onDelete('cascade');
            });
        }

        if (!Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('online_quiz_id');
                $table->unsignedInteger('student_record_id');
                $table->text('answers_json')->nullable();
                $table->decimal('score', 6, 2)->default(0);
                $table->decimal('total_marks', 6, 2)->default(0);
                $table->decimal('percentage', 5, 2)->default(0);
                $table->boolean('passed')->default(false);
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();

                $table->foreign('online_quiz_id')->references('id')->on('online_quizzes')->onDelete('cascade');
                $table->foreign('student_record_id')->references('id')->on('student_records')->onDelete('cascade');
                $table->unique(['online_quiz_id', 'student_record_id']);
            });
        }

        if (!Schema::hasTable('forum_threads')) {
            Schema::create('forum_threads', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('my_class_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->string('title', 191);
                $table->text('body');
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_locked')->default(false);
                $table->timestamps();

                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('set null');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('set null');
            });
        }

        if (!Schema::hasTable('forum_replies')) {
            Schema::create('forum_replies', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('forum_thread_id');
                $table->unsignedInteger('user_id');
                $table->text('body');
                $table->boolean('is_solution')->default(false);
                $table->timestamps();

                $table->foreign('forum_thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('forum_replies');
        Schema::dropIfExists('forum_threads');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('online_quizzes');
        Schema::dropIfExists('online_classes');
        Schema::dropIfExists('lesson_plans');
        Schema::dropIfExists('homework_submissions');
        Schema::dropIfExists('homeworks');
    }
}
