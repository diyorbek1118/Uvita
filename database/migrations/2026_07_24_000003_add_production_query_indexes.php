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
            $table->index(['status', 'category_id'], 'products_status_category_idx');
            $table->index(['status', 'created_at'], 'products_status_created_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'orders_status_created_idx');
            $table->index(['user_id', 'created_at'], 'orders_user_created_idx');
            $table->index(['courier_id', 'status'], 'orders_courier_status_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->index(['status', 'created_at'], 'payments_status_created_idx');
            $table->index(['provider', 'status'], 'payments_provider_status_idx');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->index(['product_id', 'status'], 'reviews_product_status_idx');
            $table->index(['status', 'created_at'], 'reviews_status_created_idx');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->index(['role', 'is_active'], 'staff_role_active_idx');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropIndex('products_status_category_idx');
            $table->dropIndex('products_status_created_idx');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropIndex('orders_status_created_idx');
            $table->dropIndex('orders_user_created_idx');
            $table->dropIndex('orders_courier_status_idx');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_status_created_idx');
            $table->dropIndex('payments_provider_status_idx');
        });

        Schema::table('reviews', function (Blueprint $table): void {
            $table->dropIndex('reviews_product_status_idx');
            $table->dropIndex('reviews_status_created_idx');
        });

        Schema::table('staff', function (Blueprint $table): void {
            $table->dropIndex('staff_role_active_idx');
        });
    }
};
