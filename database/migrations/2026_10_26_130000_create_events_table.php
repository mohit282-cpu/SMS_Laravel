<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('events')) {
            Schema::create('events', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 191);
                $table->string('title_ne', 191)->nullable();
                $table->text('description')->nullable();
                $table->string('category', 50)->default('school_event');
                $table->date('start_date')->index();
                $table->date('end_date')->index();
                $table->string('start_date_bs', 10)->index();
                $table->string('end_date_bs', 10)->index();
                $table->string('start_time', 15)->nullable();
                $table->string('end_time', 15)->nullable();
                $table->boolean('is_all_day')->default(true);
                $table->boolean('is_published')->default(true)->index();
                $table->boolean('is_closure')->default(false);
                $table->unsignedInteger('academic_year_id')->nullable()->index();
                $table->string('session', 50)->nullable()->index();
                $table->string('target_audience', 30)->default('all')->index();
                $table->unsignedInteger('my_class_id')->nullable()->index();
                $table->unsignedInteger('section_id')->nullable()->index();
                $table->string('location', 191)->nullable();
                $table->text('instructions')->nullable();
                $table->unsignedInteger('exam_id')->nullable()->index();
                $table->unsignedInteger('created_by')->nullable()->index();
                $table->unsignedInteger('updated_by')->nullable();
                $table->timestamps();
            });

            // Preserve existing holidays by importing them into the events table
            if (Schema::hasTable('holidays')) {
                $holidays = DB::table('holidays')->get();
                foreach ($holidays as $h) {
                    $category = 'school_event';
                    if ($h->type === 'public_holiday') {
                        $category = 'public_holiday';
                    } elseif ($h->type === 'school_holiday') {
                        $category = 'school_holiday';
                    } elseif ($h->type === 'exam_period') {
                        $category = 'examination';
                    } elseif ($h->type === 'academic_event') {
                        $category = 'school_event';
                    }

                    DB::table('events')->insert([
                        'title' => $h->title,
                        'title_ne' => $h->title_ne,
                        'description' => $h->description,
                        'category' => $category,
                        'start_date' => $h->start_date,
                        'end_date' => $h->end_date,
                        'start_date_bs' => $h->start_date_bs,
                        'end_date_bs' => $h->end_date_bs,
                        'is_all_day' => true,
                        'is_published' => true,
                        'is_closure' => (bool)$h->is_closure,
                        'created_by' => $h->created_by,
                        'created_at' => $h->created_at ?? now(),
                        'updated_at' => $h->updated_at ?? now(),
                    ]);
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
        Schema::dropIfExists('events');
    }
}
