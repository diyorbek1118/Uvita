<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            // Mahsulot (e'lon) bo'yicha sharh — rated_id o'rniga listing_id ishlatiladi.
            // Shaxslar o'rtasidagi baholashda listing_id null qoladi.
            $table->foreignId('listing_id')
                ->nullable()
                ->after('deal_id')
                ->constrained('listings')
                ->cascadeOnDelete();

            $table->foreignId('rated_id')->nullable()->change();

            $table->index(['listing_id', 'rater_id']);
        });
    }

    public function down(): void
    {
        Schema::table('ratings', function (Blueprint $table): void {
            $table->dropForeign(['listing_id']);
            $table->dropIndex(['listing_id', 'rater_id']);
            $table->dropColumn('listing_id');

            $table->foreignId('rated_id')->nullable(false)->change();
        });
    }
};
