<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_profiles', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('seller_id')->unique();
            $table->string('business_name');
            $table->string('legal_type');
            $table->string('tin', 20)->unique();
            $table->string('phone', 20);
            $table->string('region');
            $table->string('district');
            $table->string('address');
            $table->string('bank_account', 32);
            $table->string('bank_mfo', 10);
            $table->boolean('terms_accepted')->default(false);
            $table->boolean('is_verified')->default(false)->index();
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_profiles');
    }
};
