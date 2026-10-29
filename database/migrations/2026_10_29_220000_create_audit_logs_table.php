<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name_snapshot', 150)->nullable();
            $table->string('actor_role_snapshot', 50)->nullable();
            $table->string('action', 100);
            $table->string('category', 50); // authentication, academics, students, finance, administration, security
            $table->text('description');
            $table->string('auditable_type', 150)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('request_id', 64)->nullable();
            $table->string('status', 20)->default('success'); // success, failure
            $table->timestamps();

            // Indexes for high performance querying, filtering, metrics, and live stream polling
            $table->index('created_at');
            $table->index(['category', 'created_at']);
            $table->index(['action', 'created_at']);
            $table->index(['actor_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_logs');
    }
}
