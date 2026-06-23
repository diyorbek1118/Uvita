<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending');
            $table->json('address');
            $table->string('phone');
            $table->string('phone_secondary')->nullable();
            $table->string('delivery_time');
            $table->string('courier_note')->nullable();
            $table->bigInteger('delivery_price');
            $table->bigInteger('total_price');
            $table->bigInteger('grand_total');
            $table->integer('not_found_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
