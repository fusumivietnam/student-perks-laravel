<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['offers', 'articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->boolean('slug_was_published')->default(false);
            });

            // Existing drafts have no reliable publication history.
            DB::table($table)->where('is_published', true)->update(['slug_was_published' => true]);
        }
    }

    public function down(): void
    {
        foreach (['offers', 'articles', 'pages'] as $table) {
            Schema::table($table, function (Blueprint $table): void {
                $table->dropColumn('slug_was_published');
            });
        }
    }
};
