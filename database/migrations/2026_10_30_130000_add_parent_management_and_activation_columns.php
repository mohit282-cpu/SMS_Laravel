<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddParentManagementAndActivationColumns extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'activation_token')) {
                $table->string('activation_token', 64)->nullable()->index()->after('remember_token');
            }
            if (!Schema::hasColumn('users', 'activation_token_expires_at')) {
                $table->timestamp('activation_token_expires_at')->nullable()->after('activation_token');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->index()->after('activation_token_expires_at');
            }
        });

        Schema::table('student_records', function (Blueprint $table) {
            if (!Schema::hasColumn('student_records', 'parent_relationship')) {
                $table->string('parent_relationship', 50)->nullable()->after('my_parent_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('student_records', function (Blueprint $table) {
            if (Schema::hasColumn('student_records', 'parent_relationship')) {
                $table->dropColumn('parent_relationship');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $dropCols = [];
            if (Schema::hasColumn('users', 'activation_token')) {
                $dropCols[] = 'activation_token';
            }
            if (Schema::hasColumn('users', 'activation_token_expires_at')) {
                $dropCols[] = 'activation_token_expires_at';
            }
            if (Schema::hasColumn('users', 'is_active')) {
                $dropCols[] = 'is_active';
            }
            if (!empty($dropCols)) {
                $table->dropColumn($dropCols);
            }
        });
    }
}
