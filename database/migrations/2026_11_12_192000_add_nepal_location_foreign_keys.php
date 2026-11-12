<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddNepalLocationForeignKeys extends Migration
{
    public function up()
    {
        $this->addForeignKeysIfMissing('student_records', [
            'province_id' => 'nepal_provinces',
            'district_id' => 'nepal_districts',
            'local_level_id' => 'nepal_local_levels',
        ]);

        $this->addForeignKeysIfMissing('users', [
            'province_id' => 'nepal_provinces',
            'district_id' => 'nepal_districts',
            'local_level_id' => 'nepal_local_levels',
        ]);
    }

    public function down()
    {
        $this->dropForeignKeysIfPresent('users', ['province_id', 'district_id', 'local_level_id']);
        $this->dropForeignKeysIfPresent('student_records', ['province_id', 'district_id', 'local_level_id']);
    }

    protected function addForeignKeysIfMissing(string $table, array $columnToRefTable): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columnToRefTable as $column => $refTable) {
            if (!Schema::hasColumn($table, $column) || !Schema::hasTable($refTable)) {
                continue;
            }
            $fkName = "{$table}_{$column}_foreign";
            if (!$this->foreignKeyExists($table, $fkName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column, $refTable) {
                    $blueprint->foreign($column)->references('id')->on($refTable)->onDelete('restrict');
                });
            }
        }
    }

    protected function dropForeignKeysIfPresent(string $table, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        foreach ($columns as $column) {
            $fkName = "{$table}_{$column}_foreign";
            if ($this->foreignKeyExists($table, $fkName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($column) {
                    $blueprint->dropForeign([$column]);
                });
            }
        }
    }

    protected function foreignKeyExists(string $table, string $fkName): bool
    {
        $driver = DB::getDriverName();
        if ($driver !== 'mysql') {
            return false;
        }

        $dbName = DB::getDatabaseName();
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $dbName)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_NAME', $fkName)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
}

