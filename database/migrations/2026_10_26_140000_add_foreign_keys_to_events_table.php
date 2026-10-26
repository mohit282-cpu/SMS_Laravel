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
            // Step 1: Detect orphaned references. Do NOT silently nullify or delete historical data.
            $orphanErrors = [];

            if (Schema::hasTable('academic_years')) {
                $orphanAcademicYears = DB::table('events')
                    ->whereNotNull('academic_year_id')
                    ->whereNotIn('academic_year_id', DB::table('academic_years')->pluck('id'))
                    ->pluck('academic_year_id')
                    ->unique()
                    ->toArray();

                if (!empty($orphanAcademicYears)) {
                    $orphanErrors[] = sprintf(
                        "academic_year_id: %d invalid reference(s) to non-existent academic_years (IDs: %s)",
                        count($orphanAcademicYears),
                        implode(', ', array_slice($orphanAcademicYears, 0, 10))
                    );
                }
            }

            if (Schema::hasTable('exams')) {
                $orphanExams = DB::table('events')
                    ->whereNotNull('exam_id')
                    ->whereNotIn('exam_id', DB::table('exams')->pluck('id'))
                    ->pluck('exam_id')
                    ->unique()
                    ->toArray();

                if (!empty($orphanExams)) {
                    $orphanErrors[] = sprintf(
                        "exam_id: %d invalid reference(s) to non-existent exams (IDs: %s)",
                        count($orphanExams),
                        implode(', ', array_slice($orphanExams, 0, 10))
                    );
                }
            }

            if (Schema::hasTable('my_classes')) {
                $orphanClasses = DB::table('events')
                    ->whereNotNull('my_class_id')
                    ->whereNotIn('my_class_id', DB::table('my_classes')->pluck('id'))
                    ->pluck('my_class_id')
                    ->unique()
                    ->toArray();

                if (!empty($orphanClasses)) {
                    $orphanErrors[] = sprintf(
                        "my_class_id: %d invalid reference(s) to non-existent my_classes (IDs: %s)",
                        count($orphanClasses),
                        implode(', ', array_slice($orphanClasses, 0, 10))
                    );
                }
            }

            if (Schema::hasTable('sections')) {
                $orphanSections = DB::table('events')
                    ->whereNotNull('section_id')
                    ->whereNotIn('section_id', DB::table('sections')->pluck('id'))
                    ->pluck('section_id')
                    ->unique()
                    ->toArray();

                if (!empty($orphanSections)) {
                    $orphanErrors[] = sprintf(
                        "section_id: %d invalid reference(s) to non-existent sections (IDs: %s)",
                        count($orphanSections),
                        implode(', ', array_slice($orphanSections, 0, 10))
                    );
                }
            }

            if (Schema::hasTable('users')) {
                $orphanUsers = DB::table('events')
                    ->whereNotNull('created_by')
                    ->whereNotIn('created_by', DB::table('users')->pluck('id'))
                    ->pluck('created_by')
                    ->unique()
                    ->toArray();

                if (!empty($orphanUsers)) {
                    $orphanErrors[] = sprintf(
                        "created_by: %d invalid reference(s) to non-existent users (IDs: %s)",
                        count($orphanUsers),
                        implode(', ', array_slice($orphanUsers, 0, 10))
                    );
                }
            }

            if (!empty($orphanErrors)) {
                throw new \RuntimeException(
                    "Cannot add foreign keys to 'events' table because unresolved orphaned references exist:\n- "
                    . implode("\n- ", $orphanErrors)
                    . "\nAutomatic nullification was prevented to protect historical records. Please audit, reassign, or resolve these orphaned records before running this migration."
                );
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
            $foreignKeys = [
                'academic_year_id',
                'exam_id',
                'my_class_id',
                'section_id',
                'created_by',
            ];

            foreach ($foreignKeys as $fk) {
                try {
                    Schema::table('events', function (Blueprint $table) use ($fk) {
                        $table->dropForeign([$fk]);
                    });
                } catch (\Throwable $e) {
                    // Ignore if FK was not created or already dropped
                }
            }
        }
    }
}
