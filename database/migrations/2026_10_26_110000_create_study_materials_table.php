<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudyMaterialsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('study_materials')) {
            Schema::create('study_materials', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('file_path');
                $table->string('file_name');
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('file_type', 50)->nullable();
                $table->unsignedInteger('my_class_id');
                $table->unsignedInteger('section_id')->nullable();
                $table->unsignedInteger('subject_id')->nullable();
                $table->unsignedInteger('teacher_id');
                $table->timestamps();

                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
                $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
                $table->foreign('teacher_id')->references('id')->on('users')->onDelete('cascade');
                $table->index(['my_class_id', 'section_id', 'subject_id', 'teacher_id'], 'sm_class_sec_sub_teach_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('study_materials');
    }
}
