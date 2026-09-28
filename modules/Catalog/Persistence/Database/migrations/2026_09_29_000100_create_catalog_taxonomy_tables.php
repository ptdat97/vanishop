<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('slug', 128);
            // Materialized path các id tổ tiên + chính nó: "/12/57/" — truy vấn cây con bằng LIKE "/12/%".
            $table->string('path', 512);
            $table->unsignedTinyInteger('depth');
            $table->unsignedInteger('position')->default(0);
            $table->string('status', 16)->default('active');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
            $table->index(['brand_id', 'parent_id', 'position']);
            $table->index(['brand_id', 'path']);
        });

        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->unique(['category_id', 'locale']);
        });

        Schema::create('attributes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('code', 64);
            $table->string('kind', 16);           // spec | internal
            $table->string('input_type', 16);     // select | multiselect | text | boolean
            $table->boolean('is_filterable')->default(false);
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->unique(['brand_id', 'code']);
        });

        Schema::create('attribute_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['attribute_id', 'locale']);
        });

        Schema::create('attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->string('code', 64);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['attribute_id', 'code']);
        });

        Schema::create('attribute_value_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_value_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('label');
            $table->unique(['attribute_value_id', 'locale']);
        });

        Schema::create('colors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('code', 32);
            $table->string('color_family', 16);
            $table->char('hex', 7)->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->unique(['brand_id', 'code']);
            $table->index(['brand_id', 'color_family']);
        });

        Schema::create('color_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('color_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->unique(['color_id', 'locale']);
        });

        Schema::create('sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('size_system', 16);    // alpha | numeric | vn | us | eu | one
            $table->string('code', 16);
            $table->unsignedInteger('sort_order');
            $table->timestamps();
            $table->unique(['brand_id', 'size_system', 'code']);
            $table->index(['brand_id', 'size_system', 'sort_order']);
        });
    }

    public function down(): void
    {
        foreach (['sizes', 'color_translations', 'colors', 'attribute_value_translations', 'attribute_values', 'attribute_translations', 'attributes', 'category_translations', 'categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
