<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Email là định danh đăng nhập mặc định (Core 0.5.12): tài khoản tạo qua link xác minh email; đơn khách vãng lai gắn vào
| tài khoản theo email đã xác minh. Null = email chưa xác minh (nhập ở checkout, đổi trong hồ sơ, tài khoản OTP của plugin).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->timestamp('email_verified_at')->nullable()->after('phone_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table): void {
            $table->dropColumn('email_verified_at');
        });
    }
};
