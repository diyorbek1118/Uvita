<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Koordinatalar aniqlik darajasi: 'address' (aniq manzil) yoki 'region'
 * (hudud markazi — taxminiy). Kuryer xaritada taxminiy marker ko'rsa
 * tushunishi uchun.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('geo_level')->nullable()->after('lng');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn('geo_level');
        });
    }
};
