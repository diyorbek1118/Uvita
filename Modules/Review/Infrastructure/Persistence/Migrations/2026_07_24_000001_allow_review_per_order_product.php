<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            // MySQL foreign key order_id uchun alohida indeks talab qiladi.
            // Avval uni yaratib, keyin eski unique indeksni composite unique ga almashtiramiz.
            $table->index('order_id', 'reviews_order_id_index');
            $table->dropUnique('reviews_order_id_unique');
            $table->unique(['order_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropUnique(['order_id', 'product_id']);
            $table->unique('order_id');
            $table->dropIndex('reviews_order_id_index');
        });
    }
};
