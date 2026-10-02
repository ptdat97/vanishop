<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plugins', function (Blueprint $table) {
            $table->string('id', 128)->primary();
            $table->string('version', 32);
            $table->string('status', 16);
            $table->text('last_error')->nullable();
            $table->dateTime('installed_at');
            $table->timestamps();
        });

    }

    public function down(): void
    {
        Schema::dropIfExists('plugins');
    }
};
