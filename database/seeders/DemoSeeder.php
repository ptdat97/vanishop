<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Products\ProductInput;
use Modules\Catalog\Application\Products\ProductService;
use Modules\Catalog\Application\Products\StyleColorService;
use Modules\Catalog\Application\Products\VariantService;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Pricing\Application\PriceListService;
use Modules\Promotion\Application\PromotionService;
use Modules\Promotion\Persistence\Models\Promotion;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Persistence\Models\LegalEntity;

/**
 * Dữ liệu demo cho môi trường dev: một cửa hàng, hai thương hiệu trong cùng catalog (ADR-028).
 * Chạy: php artisan db:seed --class=DemoSeeder
 */
final class DemoSeeder extends Seeder
{
    public function run(CurrentContext $context, CategoryService $categories, TaxonomyService $taxonomy, ProductService $products, StyleColorService $styleColors, VariantService $variants, PriceListService $prices, LocationService $locations, StockAdjustmentService $stock, PromotionService $promotions): void
    {
        $context->runAs(ContextScope::system('demo seeder'), function () use ($categories, $taxonomy, $products, $styleColors, $variants, $prices, $locations, $stock, $promotions): void {
            LegalEntity::query()->firstOrCreate(['code' => 'VANI'], ['name' => 'Công ty TNHH Vani Fashion', 'tax_code' => '0100000001']);

            if (! Category::query()->where('slug', 'nu')->exists()) {
                $this->seedCatalog($categories, $taxonomy);
            }

            $created = false;
            foreach ([['lumiere', 'Lumière', 'LM'], ['urbanx', 'Urbanx', 'UX']] as $position => [$slug, $name, $code]) {
                $brand = Brand::query()->firstOrCreate(['slug' => $slug], ['code' => $code, 'name' => $name, 'position' => $position]);
                if ($brand->wasRecentlyCreated) {
                    $this->seedProducts($brand, $products, $styleColors, $variants);
                    $created = true;
                }
            }

            if ($created) {
                $this->seedPrices($prices);
            }

            if (! Promotion::query()->exists()) {
                $welcome = $promotions->save(['name' => 'Chào bạn mới — giảm 10%', 'status' => 'active', 'starts_at' => null, 'ends_at' => null, 'priority' => 0,
                    'stacking' => 'combinable', 'requires_voucher' => true, 'action_type' => 'percent_off', 'action_config' => ['basis_points' => 1000], 'usage_limit' => null, 'budget_amount' => null]);
                $promotions->createVouchers($welcome, 'CHAO10', null, 1, null, null);
            }

            if (! Location::query()->where('code', 'WH-HCM')->exists()) {
                $this->seedInventory($locations, $stock);
            }
        });
    }

    /**
     * Kho tổng giao online + một cửa hàng (chỉ bán tại quầy).
     */
    private function seedInventory(LocationService $locations, StockAdjustmentService $stock): void
    {
        $defaults = ['province_code' => '79', 'stock_authority' => Location::AUTHORITY_VANISHOP, 'status' => 'active', 'accepts_returns' => true];

        $warehouse = $locations->save([...$defaults, 'code' => 'WH-HCM', 'name' => 'Kho tổng Thủ Đức', 'type' => 'warehouse', 'address' => 'TP. Thủ Đức, TP. Hồ Chí Minh',
            'ships_online_orders' => true, 'allows_pickup' => false, 'priority' => 10]);
        $store = $locations->save([...$defaults, 'code' => 'ST-Q1', 'name' => 'Cửa hàng Quận 1', 'type' => 'store', 'address' => 'TP. Hồ Chí Minh',
            'ships_online_orders' => false, 'allows_pickup' => true, 'priority' => 0]);

        foreach (Variant::query()->orderBy('id')->get() as $index => $variant) {
            // Vài SKU hết hàng online / sắp hết để thử storefront.
            $online = [0, 2, 8, 15, 30][$index % 5];
            if ($online > 0) {
                $stock->adjust($warehouse, $variant->id, $online, 'Tồn đầu kỳ (demo)');
            }
            $stock->adjust($store, $variant->id, 3, 'Tồn đầu kỳ (demo)');
        }
    }

    private function seedProducts(Brand $brand, ProductService $products, StyleColorService $styleColors, VariantService $variants): void
    {
        $sizeIds = Size::query()->whereIn('code', ['S', 'M', 'L'])->pluck('id')->all();

        $category = Category::query()->where('slug', 'ao-so-mi')->firstOrFail();
        $material = Attribute::query()->where('code', 'material')->firstOrFail();
        $colors = Color::query()->pluck('id', 'code');
        $prefix = strtolower($brand->code);

        foreach ([['SH01', 'ao-so-mi-lua', 'Áo sơ mi lụa', 'Silk shirt', 'silk', ['IVR', 'BLK']], ['SH02', 'ao-so-mi-linen', 'Áo sơ mi linen', 'Linen shirt', 'linen', ['BEI']]] as [$code, $slug, $vi, $en, $materialCode, $colorCodes]) {
            $style = $products->create(new ProductInput(
                styleCode: $brand->code.'-'.$code,
                slug: "{$prefix}-{$slug}",
                status: StyleStatus::Active,
                translations: ['vi' => ['name' => "{$vi} {$brand->name}"], 'en' => ['name' => "{$brand->name} {$en}"]],
                categoryIds: [$category->id],
                primaryCategoryId: $category->id,
                attributes: [$material->id => $material->values()->where('code', $materialCode)->value('id')],
                brandId: $brand->id,
            ));
            foreach ($colorCodes as $colorCode) {
                $styleColors->addColor($style, (int) $colors[$colorCode]);
            }
            $variants->generate($style, $sizeIds);
        }
    }

    /**
     * Bảng giá niêm yết + bảng giá khuyến mãi cho mẫu SH01.
     */
    private function seedPrices(PriceListService $prices): void
    {
        $base = $prices->save(['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'starts_at' => null, 'ends_at' => null, 'status' => 'active']);
        $sale = $prices->save(['code' => 'sale', 'name' => 'Khuyến mãi', 'type' => 'sale', 'priority' => 10, 'starts_at' => null, 'ends_at' => null, 'status' => 'active']);

        $variants = Variant::query()->with('style')->get();
        $prices->setPrices($base, $variants->map(fn (Variant $variant): array => ['variant_id' => $variant->id, 'amount' => str_ends_with($variant->style->style_code, 'SH01') ? 590_000 : 490_000, 'compare_at_amount' => null])->values()->all());
        $prices->setPrices($sale, $variants->filter(fn (Variant $variant): bool => str_ends_with($variant->style->style_code, 'SH01'))
            ->map(fn (Variant $variant): array => ['variant_id' => $variant->id, 'amount' => 413_000, 'compare_at_amount' => null])->values()->all());
    }

    private function seedCatalog(CategoryService $categories, TaxonomyService $taxonomy): void
    {
        $women = $categories->create(new CategoryInput('nu', CategoryStatus::Active, ['vi' => ['name' => 'Nữ'], 'en' => ['name' => 'Women']]));
        foreach ([['ao-so-mi', 'Áo sơ mi', 'Shirts'], ['dam', 'Đầm', 'Dresses'], ['quan', 'Quần', 'Trousers']] as $position => [$slug, $vi, $en]) {
            $categories->create(new CategoryInput($slug, CategoryStatus::Active, ['vi' => ['name' => $vi], 'en' => ['name' => $en]], $women->id, $position));
        }
        $categories->create(new CategoryInput('phu-kien', CategoryStatus::Active, ['vi' => ['name' => 'Phụ kiện'], 'en' => ['name' => 'Accessories']], null, 1));

        $taxonomy->saveAttribute([
            'code' => 'material', 'kind' => 'spec', 'input_type' => 'select', 'is_filterable' => true, 'position' => 0,
            'translations' => ['vi' => ['name' => 'Chất liệu'], 'en' => ['name' => 'Material']],
            'values' => [
                ['code' => 'silk', 'translations' => ['vi' => ['label' => 'Lụa'], 'en' => ['label' => 'Silk']]],
                ['code' => 'cotton', 'translations' => ['vi' => ['label' => 'Cotton'], 'en' => ['label' => 'Cotton']]],
                ['code' => 'linen', 'translations' => ['vi' => ['label' => 'Linen'], 'en' => ['label' => 'Linen']]],
            ],
        ]);

        foreach ([['IVR', 'white', '#FFFFF0', 'Trắng ngà'], ['BLK', 'black', '#111111', 'Đen'], ['BEI', 'beige', '#D8C3A5', 'Be']] as $position => [$code, $family, $hex, $name]) {
            $taxonomy->saveColor(['code' => $code, 'color_family' => $family, 'hex' => $hex, 'position' => $position, 'translations' => ['vi' => ['name' => $name]]]);
        }

        foreach (['XS', 'S', 'M', 'L', 'XL'] as $index => $code) {
            $taxonomy->saveSize(['size_system' => 'alpha', 'code' => $code, 'sort_order' => ($index + 1) * 10]);
        }
    }
}
