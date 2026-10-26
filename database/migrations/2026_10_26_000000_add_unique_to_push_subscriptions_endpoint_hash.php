<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddUniqueToPushSubscriptionsEndpointHash extends Migration
{
    /**
     * Run the migrations.
     * Safely deduplicates any legacy duplicate endpoint hashes and enforces unique constraint.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('push_subscriptions')) {
            // 1. Identify any duplicate endpoint_hash groups
            $duplicates = DB::table('push_subscriptions')
                ->select('endpoint_hash', DB::raw('COUNT(*) as total'))
                ->groupBy('endpoint_hash')
                ->having('total', '>', 1)
                ->pluck('endpoint_hash');

            // 2. Safely retain the most recent subscription per endpoint_hash
            foreach ($duplicates as $hash) {
                $latestId = DB::table('push_subscriptions')
                    ->where('endpoint_hash', $hash)
                    ->orderBy('id', 'desc')
                    ->value('id');

                if ($latestId) {
                    DB::table('push_subscriptions')
                        ->where('endpoint_hash', $hash)
                        ->where('id', '<>', $latestId)
                        ->delete();
                }
            }

            // 3. Add unique constraint if not already present
            Schema::table('push_subscriptions', function (Blueprint $table) {
                // Check if unique index already exists
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('push_subscriptions');
                if (!isset($indexes['push_subscriptions_endpoint_hash_unique'])) {
                    $table->unique('endpoint_hash', 'push_subscriptions_endpoint_hash_unique');
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
        if (Schema::hasTable('push_subscriptions')) {
            Schema::table('push_subscriptions', function (Blueprint $table) {
                $sm = Schema::getConnection()->getDoctrineSchemaManager();
                $indexes = $sm->listTableIndexes('push_subscriptions');
                if (isset($indexes['push_subscriptions_endpoint_hash_unique'])) {
                    $table->dropUnique('push_subscriptions_endpoint_hash_unique');
                }
            });
        }
    }
}
