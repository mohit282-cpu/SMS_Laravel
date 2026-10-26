<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class UpdateSchoolBrandingToSmsAcademy extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update school full name
        DB::table('settings')
            ->where('type', 'system_name')
            ->where(function ($q) {
                $q->where('description', 'CJ INSPIRED ACADEMY')
                  ->orWhere('description', 'like', '%CJ INSPIRED%');
            })
            ->update(['description' => 'School Management System Academy']);

        // Update school acronym / title
        DB::table('settings')
            ->where('type', 'system_title')
            ->where(function ($q) {
                $q->where('description', 'CJIA')
                  ->orWhere('description', 'like', '%CJ%');
            })
            ->update(['description' => 'SMS Academy']);

        // Update system contact email default
        DB::table('settings')
            ->where('type', 'system_email')
            ->where('description', 'cjacademy@cj.com')
            ->update(['description' => 'smsacademy@sms.com']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('settings')
            ->where('type', 'system_name')
            ->where('description', 'School Management System Academy')
            ->update(['description' => 'CJ INSPIRED ACADEMY']);

        DB::table('settings')
            ->where('type', 'system_title')
            ->where('description', 'SMS Academy')
            ->update(['description' => 'CJIA']);

        DB::table('settings')
            ->where('type', 'system_email')
            ->where('description', 'smsacademy@sms.com')
            ->update(['description' => 'cjacademy@cj.com']);
    }
}
