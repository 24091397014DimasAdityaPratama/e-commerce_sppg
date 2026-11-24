<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('gateway_order_id')->nullable()->unique();
            $table->string('gateway_transaction_id')->nullable();
            $table->text('qris_action_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['gateway_order_id']);
            $table->dropColumn([
                'gateway_order_id',
                'gateway_transaction_id',
                'qris_action_url',
            ]);
        });
    }
};
