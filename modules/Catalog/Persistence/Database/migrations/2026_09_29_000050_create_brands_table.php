<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Thương hiệu là thuộc tính catalog (ADR-028): trang brand, bộ lọc, báo cáo — không phải phạm vi dữ liệu.
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('slug', 64)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo_path', 512)->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 512)->nullable();
            $table->string('status', 16)->default('active');   // active | hidden
            $table->unsignedInteger('position')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['status', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
