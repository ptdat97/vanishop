<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bảng của store cache `durable` (DurableCache): dữ liệu tạm phải giữ tới khi hết hạn — tách khỏi bảng `cache` để
 * `cache:clear` không xoá; khoá dùng chung `cache_locks`. Dòng hết hạn dọn bằng `vani:cache:prune-durable`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cache_durable', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->bigInteger('expiration')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cache_durable');
    }
};
