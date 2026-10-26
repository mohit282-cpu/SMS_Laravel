<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('events')) {
            // Step 1: Clean/nullify any orphaned references prior to adding constraints
            if (Schema::hasTable('exams')) {
                DB::table('events')
                    ->whereNotNull('exam_id')
                    ->whereNotIn('exam_id', DB::table('exams')->pluck('id'))
                    ->update(['exam_id' => null]);
            }

            if (Schema::hasTable('academic_years')) {
                DB::table('events')
                    ->whereNotNull('academic_year_id')
                    ->whereNotIn('academic_year_id', DB::table('academic_years')->pluck('id'))
                    ->update(['academic_year_id' => null]);
            }

            if (Schema::hasTable('my_classes')) {
                DB::table('events')
                    ->whereNotNull('my_class_id')
                    ->whereNotIn('my_class_id', DB::table('my_classes')->pluck('id'))
                    ->update(['my_class_id' => null]);
            }

            if (Schema::hasTable('sections')) {
                DB::table('events')
                    ->whereNotNull('section_id')
                    ->whereNotIn('section_id', DB::table('sections')->pluck('id'))
                    ->update(['section_id' => null]);
            }

            if (Schema::hasTable('users')) {
                DB::table('events')
                    ->whereNotNull('created_by')
                    ->whereNotIn('created_by', DB::table('users')->pluck('id'))
                    ->update(['created_by' => null]);
            }

            // Step 2: Establish relational constraints
            Schema::table('events', function (Blueprint $table) {
                if (Schema::hasTable('academic_years')) {
                    $table->foreign('academic_year_id')
                        ->references('id')
                        ->on('academic_years')
                        ->onDelete('restrict');
                }

                if (Schema::hasTable('exams')) {
                    $table->foreign('exam_id')
                        ->references('id')
                        ->on('exams')
                        ->onDelete('cascade');
                }

                if (Schema::hasTable('my_classes')) {
                    $table->foreign('my_class_id')
                        ->references('id')
                        ->on('my_classes')
                        ->onDelete('set null');
                }

                if (Schema::hasTable('sections')) {
                    $table->foreign('section_id')
                        ->references('id')
                        ->on('sections')
                        ->onDelete('set null');
                }

                if (Schema::hasTable('users')) {
                    $table->foreign('created_by')
                        ->references('id')
                        ->on('users')
                        ->onDelete('set null');
                }
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
        if (Schema::hasTable('events')) {
            Schema::table('events', function (Blueprint $table) {
                // Drop foreign keys safely
                $table->dropForeign(['academic_year_id']);
                $table->dropForeign(['exam_id']);
                $table->dropForeign(['my_class_id']);
                $table->dropForeign(['section_id']);
                $table->dropForeign(['created_by']);
            });
        }
    }
}
