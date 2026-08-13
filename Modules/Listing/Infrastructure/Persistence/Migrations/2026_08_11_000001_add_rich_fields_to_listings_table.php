<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->string('video')->nullable()->after('images');          // video URL (upload qilingan)
            $table->string('address')->nullable()->after('district');      // aniq manzil matni
            $table->decimal('lat', 10, 7)->nullable()->after('address');   // xarita koordinatasi
            $table->decimal('lng', 10, 7)->nullable()->after('lat');       // xarita koordinatasi
            $table->json('details')->nullable()->after('lng');             // batafsil ma'lumot (navi, sana, sifat...)
            $table->json('contacts')->nullable()->after('details');        // bog'lanishlar [{type, value}]
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table): void {
            $table->dropColumn(['video', 'address', 'lat', 'lng', 'details', 'contacts']);
        });
    }
};
