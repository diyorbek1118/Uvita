<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Xodimlar (kuryerlar) FCM device token'lari.
 * Bitta xodimda bir nechta qurilma bo'lishi mumkin — alohida jadval.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->string('token', 512);
            $table->string('platform', 20)->default('android');
            $table->timestamps();

            $table->unique(['staff_id', 'token']);
            $table->foreign('staff_id')
                ->references('id')
                ->on('staff')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_device_tokens');
    }
};
