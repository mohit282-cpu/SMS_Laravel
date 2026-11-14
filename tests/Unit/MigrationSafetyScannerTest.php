<?php

namespace Tests\Unit;

use Tests\TestCase;

class MigrationSafetyScannerTest extends TestCase
{
    private function runScanner(string $filePath, bool $allowWarnings = false): array
    {
        $script = base_path('.github/scripts/check-migration-safety.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
            . ($allowWarnings ? ' --allow-warnings' : '')
            . ' --file=' . escapeshellarg($filePath) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        return [$code, implode("\n", $output)];
    }

    public function test_safe_migration_with_comments_and_strings_passes()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_safe_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        // Note: never call ->dropColumn('foo') or Schema::dropIfExists('bar') here
        $note = '->dropTable("fake") and ->dropColumn("fake") inside string';
        Schema::table('users', function (Blueprint $table) {
            $table->string('note', 50)->nullable();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('note');
        });
    }
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertEquals(0, $code, $out);
            $this->assertStringNotContainsString('::error::', $out);
            $this->assertStringNotContainsString('::warning::', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_destructive_up_fails_by_default()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_bad_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
class BadMig {
    public function up() {
        Schema::table('users', function ($t) {
            $t->dropColumn('email');
        });
    }
    public function down() {}
}
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('dropColumn', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_static_schema_drop_and_rename_are_detected()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_static_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('old_table');
    }
    public function down() {}
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('Schema::dropIfExists', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_raw_destructive_sql_is_detected()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_raw_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        DB::statement("DROP TABLE students_archive");
    }
    public function down() {}
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('raw destructive SQL', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_generic_unverified_annotation_is_rejected()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_gen_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
/**
 * @approved-destructive: please let this through
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('critical_table');
    }
    public function down() {}
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('Generic @approved-destructive string rejected', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_expired_approval_is_rejected()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_exp_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
/**
 * @approved-destructive: {"operation": "Schema::dropIfExists", "justification": "test", "preservationStrategy": "backup", "recoveryStrategy": "restore", "owner": "admin", "approvalDate": "2020-01-01", "expires": "2020-01-02"}
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('old_table');
    }
    public function down() {}
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('Approval expired', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_valid_complete_unexpired_approval_passes()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_valid_appr_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
/**
 * @approved-destructive: {"operation": "Schema::dropIfExists", "justification": "Verified decommission of obsolete temp table", "preservationStrategy": "Data exported to cold storage", "recoveryStrategy": "Re-run create table", "owner": "mohit282-cpu", "approvalDate": "2026-11-14", "expires": "2026-12-31"}
 */
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::dropIfExists('obsolete_temp_table');
    }
    public function down() {}
};
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertEquals(0, $code, $out);
            $this->assertStringContainsString('APPROVED by mohit282-cpu', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_missing_down_method_fails_by_default()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_nodown_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
class NoDownMig {
    public function up()
    {
        Schema::create('temp_items', function ($t) { $t->increments('id'); });
    }
}
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp);
            $this->assertNotEquals(0, $code);
            $this->assertStringContainsString('missing down() rollback method', $out);
        } finally {
            @unlink($tmp);
        }
    }
}
