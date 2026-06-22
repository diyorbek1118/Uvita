<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('otp_attempts', function (Blueprint $table): void {
            $table->id();
            $table->string('phone');
            $table->string('code');
            $table->string('type')->default('login');
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamp('expires_at');
            $table->boolean('is_verified')->default(false);
            $table->timestamps();

            $table->index(['phone', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otp_attempts');
    }
};
