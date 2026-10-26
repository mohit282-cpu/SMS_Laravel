<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNoticesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('notices')) {
            Schema::create('notices', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title');
                $table->text('content');
                $table->date('notice_date');
                $table->string('notice_date_bs', 20)->nullable();
                $table->date('expiry_date')->nullable();
                $table->string('expiry_date_bs', 20)->nullable();
                $table->unsignedInteger('created_by')->nullable();
                $table->string('role_visibility', 50)->default('all');
                $table->unsignedInteger('my_class_id')->nullable();
                $table->unsignedInteger('section_id')->nullable();
                $table->boolean('is_published')->default(true);
                $table->timestamps();

                $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
                $table->foreign('my_class_id')->references('id')->on('my_classes')->onDelete('cascade');
                $table->foreign('section_id')->references('id')->on('sections')->onDelete('cascade');
                $table->index(['notice_date', 'expiry_date', 'is_published']);
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
        Schema::dropIfExists('notices');
    }
}
