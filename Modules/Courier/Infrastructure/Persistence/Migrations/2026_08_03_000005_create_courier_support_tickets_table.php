<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_support_tickets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->string('category', 50);
            $table->text('message');
            $table->string('status', 30)->default('open')->index();
            $table->text('admin_reply')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['courier_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_support_tickets');
    }
};
