<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->json('page_blocks')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table): void {
            $table->dropColumn('page_blocks');
        });
    }
};
