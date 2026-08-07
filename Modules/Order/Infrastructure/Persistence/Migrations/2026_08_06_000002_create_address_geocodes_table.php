<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Geokodlash natijalari keshi — bir xil manzil uchun Nominatim'ga qayta
 * so'rov ketmasligi uchun. Xitlar ham, misslar ham (null) saqlanadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('address_geocodes', function (Blueprint $table): void {
            $table->id();
            $table->string('query')->unique();
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('address_geocodes');
    }
};
