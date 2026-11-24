<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type');
            $table->integer('quantity');
            $table->unsignedInteger('stock_before');
            $table->unsignedInteger('stock_after');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'created_at']);
        });

        Schema::create('supplier_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('operasional');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['supplier_id', 'expense_date']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_status')->default('not_started');
            $table->string('courier_name')->nullable();
            $table->string('tracking_number')->nullable();
            $table->string('delivery_proof_path')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'delivery_status',
                'courier_name',
                'tracking_number',
                'delivery_proof_path',
            ]);
        });

        Schema::dropIfExists('supplier_expenses');
        Schema::dropIfExists('stock_movements');
    }
};
