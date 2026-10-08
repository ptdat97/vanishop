<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Bằng chứng diễn tập khôi phục backup (go-live gate, operations §8): mỗi lần chạy vani:backup:drill một dòng.
        Schema::create('backup_drills', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 64);
            $table->string('backup_path');
            $table->timestamp('backup_created_at')->nullable();
            $table->string('status', 16); // ok | failed
            $table->unsignedInteger('duration_ms');
            $table->json('details');
            $table->timestamp('created_at');
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backup_drills');
    }
};
