<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentIdempotencyKeysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('payment_idempotency_keys')) {
            Schema::create('payment_idempotency_keys', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('user_id')->default(0)->index();
                $table->string('operation', 50)->default('payment.pay_now')->index();
                $table->string('idempotency_key', 120);
                $table->string('payload_hash', 64)->index();
                $table->string('status', 20)->default('completed')->index(); // processing, completed, failed
                $table->unsignedInteger('payment_record_id')->nullable()->index();
                $table->decimal('amount', 12, 2)->default(0.00);
                $table->integer('response_code')->default(200);
                $table->text('response_body')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'operation', 'idempotency_key'], 'uniq_user_op_idem_key');
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
        Schema::dropIfExists('payment_idempotency_keys');
    }
}
