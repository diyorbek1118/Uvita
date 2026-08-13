<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->bigInteger('price');               // so'm / birlik
            $table->decimal('quantity', 12, 2)->default(0); // mavjud miqdor
            $table->string('unit')->default('kg');     // kg | tonna
            $table->json('images')->nullable();
            $table->string('region')->nullable();      // viloyat
            $table->string('district')->nullable();    // tuman
            $table->string('status')->default('pending'); // pending | active | rejected | sold
            $table->string('rejection_reason')->nullable();
            $table->unsignedInteger('views')->default(0);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listings');
    }
};
