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
            $table->decimal('lat', 10, 7)->nullable()->after('address');  // kenglik
            $table->decimal('lng', 10, 7)->nullable()->after('lat');      // uzunlik
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['lat', 'lng']);
        });
    }
};
