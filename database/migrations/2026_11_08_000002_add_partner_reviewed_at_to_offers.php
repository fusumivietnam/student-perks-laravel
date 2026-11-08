<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->timestamp('partner_reviewed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table): void {
            $table->dropColumn('partner_reviewed_at');
        });
    }
};
