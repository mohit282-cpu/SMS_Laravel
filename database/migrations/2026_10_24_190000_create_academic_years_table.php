<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademicYearsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 50); // e.g. "2083/84 BS" or "2083-2084"
            $table->unsignedSmallInteger('year_bs'); // 2083
            $table->date('start_date'); // Gregorian AD date
            $table->date('end_date'); // Gregorian AD date
            $table->string('start_date_bs', 10); // "2083-01-01"
            $table->string('end_date_bs', 10); // "2083-12-30"
            $table->boolean('is_active')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->text('description')->nullable();
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
        Schema::dropIfExists('academic_years');
    }
}
