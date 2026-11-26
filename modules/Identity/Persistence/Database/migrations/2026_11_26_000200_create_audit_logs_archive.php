<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Nhật ký thao tác đã lưu trữ (db-refactor G3, `vani:audit:archive`): cùng cột với audit_logs, giữ nguyên id. Chỉ đọc khi tra
| cứu; không ghi từ ứng dụng ngoài lệnh lưu trữ.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs_archive', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->string('actor_type', 16);
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('action', 128);
            $table->string('subject_type', 64)->nullable();
            $table->string('subject_id', 64)->nullable();
            $table->json('changes')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('correlation_id', 64)->nullable();
            $table->dateTime('created_at', 6);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['actor_type', 'actor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs_archive');
    }
};
