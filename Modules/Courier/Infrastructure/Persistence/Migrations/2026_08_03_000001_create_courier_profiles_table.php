<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->unique()->constrained('staff')->cascadeOnDelete();
            $table->string('phone', 20)->nullable();
            $table->string('vehicle_type', 40)->nullable();
            $table->string('vehicle_number', 40)->nullable();
            $table->string('photo')->nullable();
            $table->boolean('is_online')->default(false)->index();
            $table->timestamp('shift_started_at')->nullable();
            $table->timestamp('shift_ended_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_profiles');
    }
};
