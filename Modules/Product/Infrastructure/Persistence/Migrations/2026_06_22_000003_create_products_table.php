<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->bigInteger('price');
            $table->integer('stock')->default(0);
            $table->string('status')->default('inactive');
            $table->json('images')->nullable();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            // manager_id — managers jadvali Admin moduli bilan qo'shiladi
            $table->unsignedBigInteger('manager_id')->nullable()->index();
            $table->string('rejection_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
