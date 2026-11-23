<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Promotion\Contracts\ManagedPromotions;
use Plugin\Bundles\BundlesServiceProvider;

/*
 * vani.bundles: combo = danh sách sản phẩm (style) + số lượng mỗi món, giảm theo % khi mua đủ bộ. Ảnh và sản phẩm là id
 * của Core, không khoá ngoại sang bảng Core (ADR-027); sản phẩm đã ẩn hoặc xoá thì combo không hiện ở storefront.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plg_bundles', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->json('translations');
            $table->unsignedBigInteger('image_media_id')->nullable();
            $table->unsignedSmallInteger('discount_basis_points');
            $table->boolean('active')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['active', 'position']);
        });

        Schema::create('plg_bundle_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bundle_id')->constrained('plg_bundles')->cascadeOnDelete();
            $table->unsignedBigInteger('style_id');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('position')->default(0);
            $table->unique(['bundle_id', 'style_id']);
            $table->index('style_id');
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            $promotions = app(ManagedPromotions::class);
            foreach (array_keys($promotions->ids(BundlesServiceProvider::ID)) as $key) {
                $promotions->remove(BundlesServiceProvider::ID, (string) $key);
            }
            app(MediaDirectory::class)->releaseUsages(BundlesServiceProvider::ID);
        });

        Schema::dropIfExists('plg_bundle_items');
        Schema::dropIfExists('plg_bundles');
    }
};
