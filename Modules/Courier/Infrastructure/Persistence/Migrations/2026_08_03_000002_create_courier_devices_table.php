<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->constrained('staff')->cascadeOnDelete();
            $table->string('token')->unique();
            $table->string('platform', 20);
            $table->string('device_name')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
            $table->index(['courier_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_devices');
    }
};
