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
            $table->unsignedInteger('reserved_stock')->default(0)->after('stock');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreignId('seller_profile_id')
                ->nullable()
                ->after('user_id')
                ->constrained('seller_profiles')
                ->nullOnDelete();
            $table->uuid('checkout_group_id')->nullable()->after('seller_profile_id')->index();
            $table->timestamp('stock_reserved_at')->nullable()->after('ready_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('seller_profile_id');
            $table->dropColumn('checkout_group_id');
            $table->dropColumn('stock_reserved_at');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('reserved_stock');
        });
    }
};
