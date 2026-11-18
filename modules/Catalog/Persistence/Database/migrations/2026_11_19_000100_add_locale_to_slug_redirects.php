<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Slug cũ theo ngôn ngữ (backlog L44): slug riêng của bản dịch đổi → link cũ của ngôn ngữ đó cũng chuyển hướng 301.
| `locale` = '' cho slug gốc (ngôn ngữ mặc định, cột `slug` của bảng gốc).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slug_redirects', function (Blueprint $table): void {
            $table->string('locale', 8)->default('')->after('subject')->comment("'' = slug gốc; mã ngôn ngữ = slug riêng của bản dịch");
            $table->dropUnique(['subject', 'slug']);
            $table->unique(['subject', 'locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('slug_redirects', function (Blueprint $table): void {
            $table->dropUnique(['subject', 'locale', 'slug']);
            $table->dropColumn('locale');
            $table->unique(['subject', 'slug']);
        });
    }
};
