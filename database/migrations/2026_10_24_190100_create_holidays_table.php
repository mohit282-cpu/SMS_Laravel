<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHolidaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('holidays', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('title_ne')->nullable();
            $table->enum('type', ['public_holiday', 'school_holiday', 'exam_period', 'academic_event', 'custom_working_day'])->default('school_holiday');
            $table->date('start_date'); // Gregorian AD date
            $table->date('end_date'); // Gregorian AD date
            $table->string('start_date_bs', 10); // "2083-07-08"
            $table->string('end_date_bs', 10); // "2083-07-10"
            $table->boolean('is_closure')->default(true); // Does school close on this day?
            $table->text('description')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('holidays');
    }
}
