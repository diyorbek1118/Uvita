<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_payouts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('courier_id')->nullable()->constrained('staff')->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->unsignedBigInteger('amount');
            $table->string('status', 30)->default('pending')->index();
            $table->string('note', 500)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('staff')->nullOnDelete();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['courier_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_payouts');
    }
};
