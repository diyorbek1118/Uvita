<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('surname')->nullable()->after('name');   // familiya
            $table->string('address')->nullable()->after('district'); // yashash manzili
            $table->string('password')->nullable()->after('address');  // hash'langan parol
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['surname', 'address', 'password']);
        });
    }
};
