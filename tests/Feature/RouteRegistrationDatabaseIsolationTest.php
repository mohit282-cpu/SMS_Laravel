<?php

namespace Tests\Feature;

use App\Http\Controllers\SupportTeam\MarkController;
use App\Http\Controllers\SupportTeam\PaymentController;
use App\Http\Controllers\SupportTeam\TimeTableController;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class RouteRegistrationDatabaseIsolationTest extends TestCase
{
    /**
     * Test that route:list runs cleanly without triggering database schema dependencies.
     */
    public function test_route_list_command_succeeds_without_database_errors()
    {
        $exitCode = Artisan::call('route:list');
        $this->assertSame(0, $exitCode, 'php artisan route:list must exit with 0.');
    }

    /**
     * Test that controllers previously querying the database in their constructors
     * can now be resolved from the container without active session dependencies.
     */
    public function test_controllers_instantiate_without_constructor_database_queries()
    {
        $tt = $this->app->make(TimeTableController::class);
        $this->assertInstanceOf(TimeTableController::class, $tt);

        $pay = $this->app->make(PaymentController::class);
        $this->assertInstanceOf(PaymentController::class, $pay);

        $mark = $this->app->make(MarkController::class);
        $this->assertInstanceOf(MarkController::class, $mark);
    }
}

