<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_profiles', function (Blueprint $table): void {
            $table->decimal('vehicle_capacity_kg', 12, 3)->default(1000)->after('vehicle_number');
            $table->unsignedSmallInteger('max_orders_per_trip')->default(10)->after('vehicle_capacity_kg');
        });

        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->decimal('pickup_latitude', 10, 7)->nullable()->after('address');
            $table->decimal('pickup_longitude', 10, 7)->nullable()->after('pickup_latitude');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('unit_weight_kg', 10, 3)->default(1)->after('unit');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('delivery_scope', 30)->default('district_center')->after('geo_level');
        });

        Schema::create('courier_trips', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->constrained('staff')->cascadeOnDelete();
            $table->string('origin_region');
            $table->string('destination_region');
            $table->string('status', 30)->default('picking_up')->index();
            $table->decimal('capacity_kg', 12, 3);
            $table->decimal('total_weight_kg', 12, 3)->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedBigInteger('cargo_value')->default(0);
            $table->unsignedBigInteger('total_courier_fee')->default(0);
            $table->unsignedBigInteger('cash_collected')->default(0);
            $table->timestamp('accepted_at');
            $table->timestamp('pickups_completed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();
            $table->timestamps();
            $table->index(['courier_id', 'status']);
            $table->index(['origin_region', 'destination_region', 'status']);
        });

        Schema::create('courier_trip_orders', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('trip_id')->constrained('courier_trips')->cascadeOnDelete();
            $table->foreignId('order_id')->unique()->constrained('orders')->cascadeOnDelete();
            $table->string('pickup_key');
            $table->unsignedSmallInteger('pickup_sequence');
            $table->unsignedSmallInteger('delivery_sequence');
            $table->decimal('weight_kg', 12, 3);
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
            $table->index(['trip_id', 'pickup_sequence']);
            $table->index(['trip_id', 'delivery_sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_trip_orders');
        Schema::dropIfExists('courier_trips');

        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('delivery_scope'));
        Schema::table('products', fn (Blueprint $table) => $table->dropColumn('unit_weight_kg'));
        Schema::table('seller_profiles', fn (Blueprint $table) => $table->dropColumn(['pickup_latitude', 'pickup_longitude']));
        Schema::table('courier_profiles', fn (Blueprint $table) => $table->dropColumn(['vehicle_capacity_kg', 'max_orders_per_trip']));
    }
};
