<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->nullable()->constrained()->restrictOnDelete();   // thương hiệu (thuộc tính catalog)
            $table->string('product_code', 64);
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

            $table->unique('product_code');
            $table->unique('slug');
            $table->index(['status', 'published_from']);
            $table->index('brand_id');
        });

        // Tìm chữ (db-refactor §4.1): FULLTEXT ngram — tìm được từ 2 ký tự và chuỗi con (văn bản không dấu có nhiều từ
        // 2 ký tự: "ao", "mi", "so"). Không dùng stopword (kết nối mysql đặt sẵn ở config/database.php; đặt lại ở đây cho
        // kết nối cấu hình khác). Chỉ MySQL; SQLite tìm bằng LIKE (SearchTextMatch).
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET SESSION innodb_ft_enable_stopword = OFF');
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (search_text) WITH PARSER ngram');
        }

        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('care_instructions')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->unique(['product_id', 'locale']);
        });

        Schema::create('category_product', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);   // thứ tự merchandising trong danh mục
            $table->primary(['category_id', 'product_id']);
            $table->index(['product_id']);
        });

        // select/multiselect: attribute_value_id; text: value_text; boolean: value_bool.
        Schema::create('product_attribute_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attribute_value_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('value_text', 1000)->nullable();
            $table->boolean('value_bool')->nullable();
            $table->index(['attribute_id', 'attribute_value_id']);
            $table->index(['product_id', 'attribute_id']);
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

        Schema::create('collection_product', function (Blueprint $table) {
            $table->foreignId('collection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->primary(['collection_id', 'product_id']);
            $table->index(['product_id']);
        });
    }

    public function down(): void
    {
        foreach (['collection_product', 'collection_translations', 'collections', 'product_attribute_values', 'category_product', 'product_translations', 'products'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
