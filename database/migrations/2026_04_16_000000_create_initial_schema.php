<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->decimal('balance', 14, 2)->default(0);
            $table->string('name', 120);
            $table->string('username', 50)->unique();
            $table->string('password');
            $table->string('two_factor_secret')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('receiver_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->dateTime('date');

            $table->index('date');
            $table->index(['sender_id', 'date']);
            $table->index(['receiver_id', 'date']);
        });

        Schema::create('payment_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->string('status', 20)->default('pending');
            $table->dateTime('date');

            $table->index('status');
            $table->index('date');
            $table->index(['target_id', 'status', 'date']);
        });

        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('ticker')->unique();
            $table->string('name');
            $table->decimal('current_price', 14, 2);
        });

        Schema::create('investments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained('stocks')->cascadeOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('buy_price', 14, 2);
            $table->decimal('sell_price', 14, 2)->nullable();
            $table->boolean('is_sold')->default(false);

            $table->index(['user_id', 'stock_id']);
        });

        Schema::create('investment_trade', function (Blueprint $table) {
            $table->id();
            $table->foreignId('investment_id')->constrained('investments')->cascadeOnDelete();
            $table->string('type');
            $table->dateTime('date');

            $table->index('date');
            $table->index(['investment_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investment_trade');
        Schema::dropIfExists('investments');
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('payment_requests');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('users');
    }
};
