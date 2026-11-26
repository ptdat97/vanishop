<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * ADR-045 — biến thể linh hoạt: danh mục loại tuỳ chọn dùng chung (Màu, Size, Chất liệu…), 1–3 trục mỗi sản phẩm, SKU là
 * một tổ hợp giá trị, ảnh theo SKU. Tạo sẵn hai loại có sẵn `color` (swatch) và `size` (button) — nhập file sản phẩm và
 * form tạo sản phẩm dùng chúng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('option_types', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('display_type', 16)->default('button'); // swatch | button | dropdown
            $table->boolean('is_filterable')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });
        Schema::create('option_type_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_type_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['option_type_id', 'locale']);
        });
        Schema::create('option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_type_id')->constrained()->restrictOnDelete();
            $table->string('code', 32);
            $table->string('group', 16)->nullable();   // nhóm giá trị (vd. hệ size alpha/vn/eu)
            $table->char('hex', 7)->nullable();         // swatch
            $table->string('family', 16)->nullable();   // nhóm màu để lọc (swatch)
            // Mã lọc ở trang danh sách (db-refactor §4.2): swatch → family, loại khác → code. Ứng dụng ghi (OptionValue::saving,
            // đổi display_type của loại ghi lại cho mọi giá trị) — không generated column vì phụ thuộc option_types.
            $table->string('filter_token', 32)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['option_type_id', 'group', 'code']);
            $table->index(['option_type_id', 'position']);
            $table->index('family');
            $table->index(['option_type_id', 'filter_token']);
        });
        Schema::create('option_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_value_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['option_value_id', 'locale']);
        });
        Schema::create('product_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['product_id', 'option_type_id']);
        });
        // Giá trị thuộc đúng trục của sản phẩm (db-refactor §4.6): bỏ trục thì giá trị của trục đi theo. Loại của giá trị
        // trùng loại của trục do ProductOptions kiểm tra.
        Schema::create('product_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('product_option_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['product_option_id', 'option_value_id']);
        });
        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('option_value_id')->constrained()->restrictOnDelete();
            $table->primary(['variant_id', 'option_type_id']);
            $table->index('option_value_id');
        });
        $this->seedBuiltInTypes();
    }

    public function down(): void
    {
        foreach (['variant_option_values', 'product_option_values', 'product_options', 'option_value_translations', 'option_values', 'option_type_translations', 'option_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }

    private function seedBuiltInTypes(): void
    {
        $now = now();
        $types = [
            ['code' => 'color', 'display_type' => 'swatch', 'position' => 0, 'names' => ['vi' => 'Màu', 'en' => 'Colour']],
            ['code' => 'size', 'display_type' => 'button', 'position' => 1, 'names' => ['vi' => 'Size', 'en' => 'Size']],
        ];
        foreach ($types as $type) {
            $id = DB::table('option_types')->insertGetId(['code' => $type['code'], 'display_type' => $type['display_type'], 'is_filterable' => true, 'position' => $type['position'], 'created_at' => $now, 'updated_at' => $now]);
            foreach ($type['names'] as $locale => $name) {
                DB::table('option_type_translations')->insert(['option_type_id' => $id, 'locale' => $locale, 'name' => $name]);
            }
        }
    }
};
