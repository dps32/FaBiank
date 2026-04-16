<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla de usuarios.
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->decimal('balance', 10, 2)->default(0);
                $table->string('name');
                $table->string('username')->unique();
                $table->string('password');
                $table->string('phone_number');
                $table->string('two_factor_secret')->nullable();
            });
        }

        // Transacciones entre usuarios.
        if (!Schema::hasTable('transactions')) {
            Schema::create('transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->dateTime('date');
            });
        }

        // Solicitudes de pago.
        if (!Schema::hasTable('payment_requests')) {
            Schema::create('payment_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
                $table->decimal('amount', 10, 2);
                $table->string('status', 20)->default('pending');
                $table->dateTime('date');
            });
        }

        // Lista de acciones.
        if (!Schema::hasTable('stocks')) {
            Schema::create('stocks', function (Blueprint $table) {
                $table->id();
                $table->string('ticker')->unique();
                $table->string('name');
                $table->decimal('current_price', 10, 2);
            });
        }

        // Inversiones de usuarios.
        if (!Schema::hasTable('investments')) {
            Schema::create('investments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
                $table->integer('quantity');
                $table->decimal('buy_price', 10, 2);
                $table->decimal('sell_price', 10, 2)->nullable();
                $table->boolean('is_sold')->default(false);
            });
        }

        // Trades de cada inversión.
        if (!Schema::hasTable('investment_trade')) {
            Schema::create('investment_trade', function (Blueprint $table) {
                $table->id();
                $table->foreignId('investment_id')->constrained('investments')->cascadeOnDelete();
                $table->string('type');
                $table->datetime('date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Borrado en orden inverso por las relaciones.
        Schema::dropIfExists('investment_trade');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('users');
    }
};
