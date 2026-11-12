<?php

namespace Tests\Unit;

use Tests\TestCase;

class MigrationSafetyScannerTest extends TestCase
{
    private function runScanner(string $filePath, bool $strict = false): array
    {
        $script = base_path('.github/scripts/check-migration-safety.php');
        $cmd = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script)
            . ($strict ? ' --strict' : '')
            . ' --file=' . escapeshellarg($filePath) . ' 2>&1';
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);

        return [$code, implode("\n", $output)];
    }

    public function test_safe_migration_with_comments_and_strings_passes_strict_scan()
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
            [$code, $out] = $this->runScanner($tmp, true);
            $this->assertEquals(0, $code, $out);
            $this->assertStringNotContainsString('::warning::', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_destructive_up_fails_in_strict_mode_even_with_unusual_formatting()
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mig_bad_') . '.php';
        file_put_contents($tmp, <<<'PHP'
<?php
class BadMig {
    public
    function
    up ( ) {
        Schema::table('users', function ($t) {
            $t -> dropColumn ( 'email' );
        });
    }
    public function down() {}
}
PHP);

        try {
            [$code, $out] = $this->runScanner($tmp, true);
            $this->assertEquals(1, $code, $out);
            $this->assertStringContainsString('dropColumn', $out);
        } finally {
            @unlink($tmp);
        }
    }

    public function test_missing_down_method_emits_warning()
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
            [$code, $out] = $this->runScanner($tmp, false);
            $this->assertEquals(0, $code, $out);
            $this->assertStringContainsString('missing down()', $out);
        } finally {
            @unlink($tmp);
        }
    }
}

