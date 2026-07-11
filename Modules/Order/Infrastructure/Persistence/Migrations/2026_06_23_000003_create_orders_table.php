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
            $table->bigInteger('total_price');                 // mahsulotlar summasi (sotuvchiga)
            $table->bigInteger('service_fee')->default(0);     // 15% xizmat haqi (mijoz to'laydi)
            $table->bigInteger('courier_fee')->default(0);     // pog'onali kuryer haqi (ichki; mijozga KO'RINMAYDI)
            $table->bigInteger('grand_total');                 // mijoz to'laydigan jami = total_price + service_fee
            $table->integer('not_found_count')->default(0);

            // Status timeline milestone vaqtlari (created_at = buyurtma tushgan vaqt)
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('delivering_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('delivery_issue_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
