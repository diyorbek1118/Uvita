<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->string('status', 30)->default('assigned')->index();
            $table->string('rejection_reason', 500)->nullable();
            $table->timestamp('assigned_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();
            $table->index(['order_id', 'status']);
            $table->index(['courier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_assignments');
    }
};
