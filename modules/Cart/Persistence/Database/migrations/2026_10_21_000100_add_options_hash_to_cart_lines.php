<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tuỳ chọn dòng giỏ do plugin khai báo (CartLineOption, ADR-030 W4): cùng variant khác tuỳ chọn là hai dòng.
 * Khoá dòng = (giỏ, variant, băm tuỳ chọn); băm rỗng = không có tuỳ chọn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->char('options_hash', 16)->default('')->after('variant_id');
            $table->unique(['cart_id', 'variant_id', 'options_hash']);
        });
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->unique(['cart_id', 'variant_id']);
        });
        Schema::table('cart_lines', function (Blueprint $table): void {
            $table->dropUnique(['cart_id', 'variant_id', 'options_hash']);
            $table->dropColumn('options_hash');
        });
    }
};
