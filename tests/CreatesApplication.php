<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // Enforce testing environment explicitly
        $app['env'] = 'testing';
        $app['config']->set('app.env', 'testing');

        // Enforce dedicated test database connection for testing
        $testConnection = env('DB_CONNECTION') ?: 'mysql_test';
        if ($testConnection === 'mysql') {
            $testConnection = 'mysql_test';
        }
        $app['config']->set('database.default', $testConnection);

        return $app;
    }
}
