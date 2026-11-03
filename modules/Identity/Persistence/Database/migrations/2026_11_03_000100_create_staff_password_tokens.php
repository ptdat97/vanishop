<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Link một lần cho nhân viên (reference-parity §3.3): đặt lại mật khẩu (hết hạn 60 phút) và lời mời đặt mật khẩu lần đầu
| (24 giờ). Bảng riêng, không dùng chung `password_reset_tokens` (khoá theo email — trùng email khách/nhân viên).
*/
return new class extends Migration
{
    public function up(): void
    {
        foreach (['staff_password_reset_tokens', 'staff_invite_tokens'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->string('email')->primary();
                $table->string('token');
                $table->timestamp('created_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_invite_tokens');
        Schema::dropIfExists('staff_password_reset_tokens');
    }
};
