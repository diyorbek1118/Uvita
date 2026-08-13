<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_proofs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('pin_hash');
            $table->text('pin_encrypted');
            $table->unsignedTinyInteger('pin_attempts')->default(0);
            $table->timestamp('pin_locked_until')->nullable();
            $table->string('method', 30)->default('pin');
            $table->string('recipient_name')->nullable();
            $table->string('photo')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignId('verified_by_courier_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_proofs');
    }
};
