<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * ADR-045 — biến thể linh hoạt: danh mục loại tuỳ chọn dùng chung (Màu, Size, Chất liệu…), 1–3 trục mỗi sản phẩm, SKU là
 * một tổ hợp giá trị, ảnh theo SKU. Chuyển dữ liệu màu × size cũ: `colors` → loại `color`, `sizes` → loại `size` (hệ size →
 * `group`), màu của sản phẩm → trục + giá trị, `variants.style_color_id|size_id` → `variant_option_values`, ảnh của màu →
 * ảnh của từng SKU cùng màu. Bỏ bảng/cột cũ (môi trường dev, không giữ tương thích ngược).
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
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['option_type_id', 'group', 'code']);
            $table->index(['option_type_id', 'position']);
            $table->index('family');
        });
        Schema::create('option_value_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('option_value_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['option_value_id', 'locale']);
        });
        Schema::create('style_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['style_id', 'option_type_id']);
        });
        Schema::create('style_option_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_value_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['style_id', 'option_value_id']);
        });
        Schema::create('variant_option_values', function (Blueprint $table): void {
            $table->foreignId('variant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('option_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('option_value_id')->constrained()->restrictOnDelete();
            $table->primary(['variant_id', 'option_type_id']);
            $table->index('option_value_id');
        });
        Schema::table('variants', function (Blueprint $table): void {
            $table->string('option_key', 64)->nullable()->after('style_id');
        });

        $this->migrateData();

        Schema::table('variants', function (Blueprint $table): void {
            // MySQL: bỏ khoá ngoại trước — chỉ mục duy nhất đang phục vụ khoá ngoại style_color_id.
            $table->dropForeign(['style_color_id']);
            $table->dropForeign(['size_id']);
        });
        Schema::table('variants', function (Blueprint $table): void {
            $table->dropUnique(['style_color_id', 'size_id']);
        });
        Schema::table('variants', function (Blueprint $table): void {
            $table->dropColumn(['style_color_id', 'size_id']);
            $table->unique(['style_id', 'option_key']);
        });
        Schema::dropIfExists('style_colors');
        Schema::dropIfExists('color_translations');
        Schema::dropIfExists('colors');
        Schema::dropIfExists('sizes');
    }

    public function down(): void
    {
        // Không quay lại mô hình màu × size cố định (ADR-045; dev: migrate:fresh).
    }

    private function migrateData(): void
    {
        $now = now();
        $type = function (string $code, string $display, int $position, array $names) use ($now): int {
            $id = DB::table('option_types')->insertGetId(['code' => $code, 'display_type' => $display, 'is_filterable' => true, 'position' => $position, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($names as $locale => $name) {
                DB::table('option_type_translations')->insert(['option_type_id' => $id, 'locale' => $locale, 'name' => $name]);
            }

            return $id;
        };
        $colorType = $type('color', 'swatch', 0, ['vi' => 'Màu', 'en' => 'Colour']);
        $sizeType = $type('size', 'button', 1, ['vi' => 'Size', 'en' => 'Size']);

        $colorValues = [];
        foreach (DB::table('colors')->orderBy('position')->orderBy('id')->get() as $color) {
            $colorValues[$color->id] = DB::table('option_values')->insertGetId([
                'option_type_id' => $colorType, 'code' => $color->code, 'hex' => $color->hex, 'family' => $color->color_family,
                'position' => $color->position, 'created_at' => $now, 'updated_at' => $now,
            ]);
            foreach (DB::table('color_translations')->where('color_id', $color->id)->get() as $translation) {
                DB::table('option_value_translations')->insert(['option_value_id' => $colorValues[$color->id], 'locale' => $translation->locale, 'name' => $translation->name]);
            }
        }
        $sizeValues = [];
        foreach (DB::table('sizes')->orderBy('size_system')->orderBy('sort_order')->get() as $size) {
            $sizeValues[$size->id] = DB::table('option_values')->insertGetId([
                'option_type_id' => $sizeType, 'code' => $size->code, 'group' => $size->size_system, 'position' => $size->sort_order,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $styleColors = DB::table('style_colors')->orderBy('style_id')->orderBy('position')->orderBy('id')->get()->keyBy('id');
        foreach ($styleColors->groupBy('style_id') as $styleId => $rows) {
            DB::table('style_options')->insert(['style_id' => $styleId, 'option_type_id' => $colorType, 'position' => 0, 'created_at' => $now, 'updated_at' => $now]);
            foreach ($rows->values() as $position => $row) {
                DB::table('style_option_values')->insert(['style_id' => $styleId, 'option_value_id' => $colorValues[$row->color_id], 'position' => $position, 'created_at' => $now, 'updated_at' => $now]);
            }
        }

        $variants = DB::table('variants')->orderBy('id')->get();
        $styleSizes = [];
        foreach ($variants as $variant) {
            $styleColor = $styleColors->get($variant->style_color_id);
            $colorValue = $colorValues[$styleColor->color_id];
            $sizeValue = $sizeValues[$variant->size_id];
            DB::table('variant_option_values')->insert([
                ['variant_id' => $variant->id, 'option_type_id' => $colorType, 'option_value_id' => $colorValue],
                ['variant_id' => $variant->id, 'option_type_id' => $sizeType, 'option_value_id' => $sizeValue],
            ]);
            DB::table('variants')->where('id', $variant->id)->update(['option_key' => "{$colorValue}-{$sizeValue}"]);
            $styleSizes[$variant->style_id][$sizeValue] = true;

            // Ảnh theo SKU (ADR-045): chép ảnh của màu cho từng SKU cùng màu.
            foreach (DB::table('mediables')->where('mediable_type', 'catalog.style_color')->where('mediable_id', $styleColor->id)->orderBy('position')->get() as $image) {
                DB::table('mediables')->insert([
                    'media_id' => $image->media_id, 'mediable_type' => 'catalog.variant', 'mediable_id' => $variant->id, 'role' => $image->role,
                    'position' => $image->position, 'alt' => $image->alt, 'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
        foreach ($styleSizes as $styleId => $values) {
            DB::table('style_options')->insert(['style_id' => $styleId, 'option_type_id' => $sizeType, 'position' => 1, 'created_at' => $now, 'updated_at' => $now]);
            $ordered = DB::table('option_values')->whereIn('id', array_keys($values))->orderBy('group')->orderBy('position')->pluck('id');
            foreach ($ordered as $position => $valueId) {
                DB::table('style_option_values')->insert(['style_id' => $styleId, 'option_value_id' => $valueId, 'position' => $position, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
        DB::table('mediables')->where('mediable_type', 'catalog.style_color')->delete();
    }
};
