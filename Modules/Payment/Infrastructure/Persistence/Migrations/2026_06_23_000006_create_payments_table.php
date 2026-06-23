<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('provider');                        // 'payme' | 'click' | 'uzum'
            $table->string('transaction_id')->unique()->nullable(); // idempotency key, webhook dan keladi
            $table->string('provider_transaction_id')->nullable();  // provider ichki ID
            $table->bigInteger('amount');                      // tiyinda (so'm * 100)
            $table->string('status')->default('pending');      // 'pending' | 'paid' | 'failed' | 'cancelled'
            $table->json('payload')->nullable();               // webhook raw data
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
