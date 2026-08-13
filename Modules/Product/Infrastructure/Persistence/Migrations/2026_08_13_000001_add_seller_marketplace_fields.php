<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('seller_id')->nullable()->after('manager_id')->index();
            $table->unsignedInteger('approved_version')->default(0)->after('seller_id');
            $table->json('fee_snapshot')->nullable()->after('price');
            $table->string('video_url')->nullable()->after('images');
            $table->unsignedTinyInteger('primary_image_index')->default(0)->after('images');
            $table->string('origin_region')->nullable()->after('category_id');
            $table->string('farmer_name')->nullable()->after('origin_region');
            $table->string('unit')->nullable()->after('farmer_name');
            $table->unsignedInteger('minimum_order_quantity')->default(1)->after('unit');
        });

        Schema::create('product_revisions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->unsignedBigInteger('seller_id')->index();
            $table->unsignedInteger('version');
            $table->json('payload');
            $table->json('fee_snapshot');
            $table->string('status')->default('pending')->index();
            $table->string('rejection_reason')->nullable();
            $table->unsignedBigInteger('reviewed_by_id')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['product_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_revisions');
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn([
                'seller_id', 'approved_version', 'fee_snapshot', 'video_url', 'primary_image_index',
                'origin_region', 'farmer_name', 'unit', 'minimum_order_quantity',
            ]);
        });
    }
};
