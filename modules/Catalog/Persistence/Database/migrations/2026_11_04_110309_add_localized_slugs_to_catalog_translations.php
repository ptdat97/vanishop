<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Slug theo ngôn ngữ (ADR-038 §4): slug trên bảng gốc là slug của ngôn ngữ mặc định; bản dịch ngôn ngữ khác có slug
| riêng (nullable — trống thì dùng slug gốc), unique theo (locale, slug). Thương hiệu một slug cho mọi ngôn ngữ.
*/
return new class extends Migration
{
    /** @var list<string> */
    private const TABLES = ['style_translations', 'category_translations', 'collection_translations'];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->string('slug', 160)->nullable()->after('locale');
                $blueprint->unique(['locale', 'slug']);
            });
        }
    }

    public function down(): void
    {
        foreach (self::TABLES as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->dropUnique(['locale', 'slug']);
                $blueprint->dropColumn('slug');
            });
        }
    }
};
