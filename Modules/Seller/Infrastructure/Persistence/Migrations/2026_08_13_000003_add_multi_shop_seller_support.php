<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table): void {
            $table->string('phone', 20)->nullable()->unique()->after('email');
        });

        DB::table('seller_profiles')->oldest('id')->get()
            ->each(function (object $profile): void {
                DB::table('staff')
                    ->where('id', $profile->seller_id)
                    ->where('role', 'seller')
                    ->whereNull('phone')
                    ->update(['phone' => $profile->phone]);
            });

        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropUnique(['seller_id']);
            $table->index('seller_id');
            $table->boolean('is_active')->default(true)->after('terms_accepted');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->unsignedBigInteger('seller_profile_id')->nullable()->after('seller_id')->index();
        });

        Schema::table('product_revisions', function (Blueprint $table): void {
            $table->unsignedBigInteger('seller_profile_id')->nullable()->after('seller_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('product_revisions', function (Blueprint $table): void {
            $table->dropColumn('seller_profile_id');
        });
        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('seller_profile_id');
        });
        Schema::table('seller_profiles', function (Blueprint $table): void {
            $table->dropColumn('is_active');
            $table->dropIndex(['seller_id']);
            $table->unique('seller_id');
        });
        Schema::table('staff', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
