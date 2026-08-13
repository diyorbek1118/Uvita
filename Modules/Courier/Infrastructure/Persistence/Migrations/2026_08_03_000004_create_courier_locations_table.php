<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_locations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedSmallInteger('accuracy')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();
            $table->index(['order_id', 'recorded_at']);
            $table->index(['courier_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_locations');
    }
};
