<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Ảnh đính kèm yêu cầu đổi trả (reference-parity §3.13-3): tối đa 5 ảnh/yêu cầu, lưu ở disk riêng tư (không phải Thư viện
 * ảnh công khai) — chỉ khách chủ đơn và nhân viên xem được qua link có chữ ký ngắn hạn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_attachments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('return_request_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 32);
            $table->string('path');
            $table->string('mime_type', 64);
            $table->unsignedInteger('size_bytes');
            $table->string('source', 16);
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_attachments');
    }
};
