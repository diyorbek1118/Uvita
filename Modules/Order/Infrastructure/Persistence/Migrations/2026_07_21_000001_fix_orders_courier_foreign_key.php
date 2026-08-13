<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['courier_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('courier_id')
                ->references('id')
                ->on('staff')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropForeign(['courier_id']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->foreign('courier_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }
};
