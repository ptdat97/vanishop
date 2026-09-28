<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Brand\Persistence\Models\Brand;
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
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Inventory\Application\LocationService;
use Modules\Inventory\Application\StockAdjustmentService;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Pricing\Application\PriceListService;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Persistence\Models\LegalEntity;

/**
 * Dữ liệu demo cho môi trường dev: 2 brand trên một domain chung theo đường dẫn (ADR-019).
 * Chạy: php artisan db:seed --class=DemoSeeder
 */
final class DemoSeeder extends Seeder
{
    public function run(CurrentContext $context, CategoryService $categories, TaxonomyService $taxonomy, ProductService $products, StyleColorService $styleColors, VariantService $variants, PriceListService $prices, LocationService $locations, StockAdjustmentService $stock): void
    {
        $context->runAs(ContextScope::system('demo seeder'), function () use ($categories, $taxonomy, $products, $styleColors, $variants, $prices, $locations, $stock): void {
            $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
            $company = LegalEntity::query()->firstOrCreate(['code' => 'VANI'], ['name' => 'Công ty TNHH Vani Fashion', 'tax_code' => '0100000001']);

            foreach ([['lumiere', 'Lumière', 'LM'], ['urbanx', 'Urbanx', 'UX']] as [$slug, $name, $code]) {
                $brand = Brand::query()->firstOrCreate(['slug' => $slug], ['legal_entity_id' => $company->id, 'code' => $code, 'name' => $name]);

                $channel = Channel::query()->firstOrCreate(['code' => "web-{$slug}"], ['name' => "Website {$name}", 'type' => 'web']);
                $channel->brands()->syncWithoutDetaching([$brand->id]);
                $channel->domains()->firstOrCreate(['host' => $host, 'path_prefix' => "/{$slug}"], ['is_primary' => true]);

                if ($brand->wasRecentlyCreated) {
                    $this->seedCatalog($brand->id, $categories, $taxonomy);
                    $this->seedProducts($brand, $products, $styleColors, $variants);
                    $this->seedPrices($brand, $channel->id, $prices);
                }
            }

            if (! Location::query()->where('code', 'WH-HCM')->exists()) {
                $this->seedInventory($company->id, $locations, $stock);
            }
        });
    }

    /**
     * Kho tổng dùng chung giao online cho cả hai brand + mỗi brand một cửa hàng (chỉ bán tại quầy).
     */
    private function seedInventory(int $legalEntityId, LocationService $locations, StockAdjustmentService $stock): void
    {
        $brands = Brand::query()->whereIn('slug', ['lumiere', 'urbanx'])->pluck('id', 'slug');
        $channels = Channel::query()->whereIn('code', ['web-lumiere', 'web-urbanx'])->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $defaults = ['legal_entity_id' => $legalEntityId, 'province_code' => '79', 'stock_authority' => Location::AUTHORITY_VANISHOP, 'status' => 'active', 'accepts_returns' => true];

        $warehouse = $locations->save([...$defaults, 'code' => 'WH-HCM', 'name' => 'Kho tổng Thủ Đức', 'type' => 'warehouse', 'address' => 'TP. Thủ Đức, TP. Hồ Chí Minh',
            'ships_online_orders' => true, 'allows_pickup' => false, 'priority' => 10], $brands->values()->map(fn ($id): int => (int) $id)->all(), $channels);

        $stores = [];
        foreach (['lumiere' => 'ST-LM-Q1', 'urbanx' => 'ST-UX-Q3'] as $slug => $code) {
            $stores[(int) $brands[$slug]] = $locations->save([...$defaults, 'code' => $code, 'name' => "Cửa hàng {$code}", 'type' => 'store', 'address' => 'TP. Hồ Chí Minh',
                'ships_online_orders' => false, 'allows_pickup' => true, 'priority' => 0], [(int) $brands[$slug]], []);
        }

        foreach (Variant::query()->whereIn('brand_id', $brands->values())->orderBy('id')->get() as $index => $variant) {
            // Vài SKU hết hàng online / sắp hết để thử storefront.
            $online = [0, 2, 8, 15, 30][$index % 5];
            if ($online > 0) {
                $stock->adjust($warehouse, $variant->id, $online, 'Tồn đầu kỳ (demo)');
            }
            $stock->adjust($stores[$variant->brand_id], $variant->id, 3, 'Tồn đầu kỳ (demo)');
        }
    }

    private function seedProducts(Brand $brand, ProductService $products, StyleColorService $styleColors, VariantService $variants): void
    {
        $sizeIds = Size::query()->where('brand_id', $brand->id)->whereIn('code', ['S', 'M', 'L'])->pluck('id')->all();

        $category = Category::query()->where('brand_id', $brand->id)->where('slug', 'ao-so-mi')->firstOrFail();
        $material = Attribute::query()->where('brand_id', $brand->id)->where('code', 'material')->firstOrFail();
        $colors = Color::query()->where('brand_id', $brand->id)->pluck('id', 'code');

        foreach ([['SH01', 'ao-so-mi-lua', 'Áo sơ mi lụa', 'Silk shirt', 'silk', ['IVR', 'BLK']], ['SH02', 'ao-so-mi-linen', 'Áo sơ mi linen', 'Linen shirt', 'linen', ['BEI']]] as [$code, $slug, $vi, $en, $materialCode, $colorCodes]) {
            $style = $products->create($brand->id, new ProductInput(
                styleCode: $brand->code.'-'.$code,
                slug: $slug,
                status: StyleStatus::Active,
                translations: ['vi' => ['name' => $vi], 'en' => ['name' => $en]],
                categoryIds: [$category->id],
                primaryCategoryId: $category->id,
                attributes: [$material->id => $material->values()->where('code', $materialCode)->value('id')],
            ));
            foreach ($colorCodes as $colorCode) {
                $styleColors->addColor($style, (int) $colors[$colorCode]);
            }
            $variants->generate($style, $sizeIds);
        }
    }

    /**
     * Bảng giá niêm yết + bảng giá khuyến mãi 30% cho sản phẩm đầu tiên.
     */
    private function seedPrices(Brand $brand, int $channelId, PriceListService $prices): void
    {
        $base = $prices->save($brand->id, ['code' => 'base', 'name' => 'Giá niêm yết', 'type' => 'base', 'priority' => 0, 'starts_at' => null, 'ends_at' => null, 'status' => 'active'], [$channelId]);
        $sale = $prices->save($brand->id, ['code' => 'sale', 'name' => 'Khuyến mãi', 'type' => 'sale', 'priority' => 10, 'starts_at' => null, 'ends_at' => null, 'status' => 'active'], [$channelId]);

        $variants = Variant::query()->where('brand_id', $brand->id)->with('style')->get();
        $prices->setPrices($base, $variants->map(fn (Variant $variant): array => ['variant_id' => $variant->id, 'amount' => str_ends_with($variant->style->style_code, 'SH01') ? 590_000 : 490_000, 'compare_at_amount' => null])->values()->all());
        $prices->setPrices($sale, $variants->filter(fn (Variant $variant): bool => str_ends_with($variant->style->style_code, 'SH01'))
            ->map(fn (Variant $variant): array => ['variant_id' => $variant->id, 'amount' => 413_000, 'compare_at_amount' => null])->values()->all());
    }

    private function seedCatalog(int $brandId, CategoryService $categories, TaxonomyService $taxonomy): void
    {
        $women = $categories->create($brandId, new CategoryInput('nu', CategoryStatus::Active, ['vi' => ['name' => 'Nữ'], 'en' => ['name' => 'Women']]));
        foreach ([['ao-so-mi', 'Áo sơ mi', 'Shirts'], ['dam', 'Đầm', 'Dresses'], ['quan', 'Quần', 'Trousers']] as $position => [$slug, $vi, $en]) {
            $categories->create($brandId, new CategoryInput($slug, CategoryStatus::Active, ['vi' => ['name' => $vi], 'en' => ['name' => $en]], $women->id, $position));
        }
        $categories->create($brandId, new CategoryInput('phu-kien', CategoryStatus::Active, ['vi' => ['name' => 'Phụ kiện'], 'en' => ['name' => 'Accessories']], null, 1));

        $taxonomy->saveAttribute($brandId, [
            'code' => 'material', 'kind' => 'spec', 'input_type' => 'select', 'is_filterable' => true, 'position' => 0,
            'translations' => ['vi' => ['name' => 'Chất liệu'], 'en' => ['name' => 'Material']],
            'values' => [
                ['code' => 'silk', 'translations' => ['vi' => ['label' => 'Lụa'], 'en' => ['label' => 'Silk']]],
                ['code' => 'cotton', 'translations' => ['vi' => ['label' => 'Cotton'], 'en' => ['label' => 'Cotton']]],
                ['code' => 'linen', 'translations' => ['vi' => ['label' => 'Linen'], 'en' => ['label' => 'Linen']]],
            ],
        ]);

        foreach ([['IVR', 'white', '#FFFFF0', 'Trắng ngà'], ['BLK', 'black', '#111111', 'Đen'], ['BEI', 'beige', '#D8C3A5', 'Be']] as $position => [$code, $family, $hex, $name]) {
            $taxonomy->saveColor($brandId, ['code' => $code, 'color_family' => $family, 'hex' => $hex, 'position' => $position, 'translations' => ['vi' => ['name' => $name]]]);
        }

        foreach (['XS', 'S', 'M', 'L', 'XL'] as $index => $code) {
            $taxonomy->saveSize($brandId, ['size_system' => 'alpha', 'code' => $code, 'sort_order' => ($index + 1) * 10]);
        }
    }
}
