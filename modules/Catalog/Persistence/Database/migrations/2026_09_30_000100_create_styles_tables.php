<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('styles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->restrictOnDelete();   // thương hiệu (thuộc tính catalog)
            $table->string('style_code', 64);
            $table->string('slug', 160);
            $table->string('status', 16)->default('draft');       // draft | active | archived
            $table->dateTime('published_from')->nullable();
            $table->dateTime('published_to')->nullable();
            $table->foreignId('primary_category_id')->nullable()->constrained('categories')->nullOnDelete();
            // Văn bản đã chuẩn hoá (không dấu) cho tìm kiếm trong DB.
            $table->text('search_text');
            $table->json('meta')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique('style_code');
            $table->unique('slug');
            $table->index(['status', 'published_from']);
            $table->index('brand_id');
        });

        Schema::create('style_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('care_instructions')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->unique(['style_id', 'locale']);
        });

        Schema::create('style_colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('color_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['style_id', 'color_id']);
        });

        Schema::create('category_style', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);   // thứ tự merchandising trong danh mục
            $table->primary(['category_id', 'style_id']);
            $table->index(['style_id']);
        });

        // select/multiselect: attribute_value_id; text: value_text; boolean: value_bool.
        Schema::create('style_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('value_text', 1000)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->index(['attribute_id', 'attribute_value_id']);
            $table->index(['style_id', 'attribute_id']);
        });

        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 128);
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique('slug');
        });

        Schema::create('collection_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->unique(['collection_id', 'locale']);
        });

        Schema::create('collection_style', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('style_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['collection_id', 'style_id']);
            $table->index(['style_id']);
        });
    }

    public function down(): void
    {
        foreach (['collection_style', 'collection_translations', 'collections', 'style_attribute_values', 'category_style', 'style_colors', 'style_translations', 'styles'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
