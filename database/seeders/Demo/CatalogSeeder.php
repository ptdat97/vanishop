<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Catalog\Application\Categories\CategoryInput;
use Modules\Catalog\Application\Categories\CategoryService;
use Modules\Catalog\Application\Collections\CollectionService;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\Data\ProductImport;
use Modules\Catalog\Domain\CategoryStatus;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Inventory\Contracts\StockImporter;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Contracts\PriceImporter;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\StoreLocale;

/**
 * Catalog demo có ảnh chụp thật (bộ ảnh trong `photos/`, trước đây là plugin vani.demo-catalog): mỗi nhóm ảnh → một
 * sản phẩm "Vani Studio", một màu, size S/M/L, xếp vào danh mục con của "Nữ" theo tên + "Hàng mới về". Giá niêm yết,
 * bảng giá sale cho vài mẫu, hai bộ sưu tập, tồn đầu kỳ (có mẫu hết / sắp hết) ở kho online và cửa hàng.
 *
 * Giá trị suy ra từ mã nhóm ảnh (crc32) → chạy lại cho cùng kết quả. Sản phẩm đã có chỉ được bổ sung phần thiếu.
 * Ảnh khác: `VANI_DEMO_IMAGES=/thư/mục` (jpg/png/webp, cấp đầu); `VANI_DEMO_IMAGE_MAX` cạnh dài tối đa (mặc định 1600) —
 * `config('vanishop.demo')`.
 */
final class CatalogSeeder extends Seeder
{
    public const BRAND = ['slug' => 'vani-studio', 'code' => 'VS', 'name' => 'Vani Studio'];

    public const NEW_ARRIVALS = 'hang-moi-ve';

    /** Danh mục con của "Nữ": slug => [tên, từ khoá đầu tên sản phẩm]. */
    public const CATEGORIES = [
        'dam' => ['Đầm', ['Đầm', 'Jumpsuit']],
        'ao' => ['Áo', ['Áo']],
        'chan-vay' => ['Chân váy', ['Chân váy', 'Váy']],
        'quan' => ['Quần', ['Quần']],
        'set-do' => ['Set đồ', ['Set']],
    ];

    private const NAMES = [
        'Đầm lụa dáng suông', 'Áo sơ mi lụa cổ đức', 'Váy midi xếp ly', 'Áo blazer dáng dài', 'Chân váy chữ A',
        'Đầm maxi hoa nhí', 'Set áo croptop quần ống rộng', 'Áo len cổ lọ', 'Đầm công sở thắt eo', 'Áo kiểu tay bồng',
        'Quần âu ống suông', 'Đầm hai dây satin', 'Áo khoác dạ dáng ngắn', 'Váy bút chì', 'Áo sơ mi linen',
        'Đầm suông cổ vuông', 'Jumpsuit ống rộng', 'Áo vest cách điệu', 'Đầm xoè tay lỡ', 'Chân váy midi lụa',
        'Áo len dệt kim mỏng', 'Đầm ôm dáng dài', 'Áo cardigan', 'Quần culottes', 'Đầm sơ mi thắt đai',
    ];

    private const COLORS = [
        ['code' => 'BLK', 'name' => 'Đen', 'hex' => '#111111', 'family' => 'black'],
        ['code' => 'IVR', 'name' => 'Ngà', 'hex' => '#F4EFE6', 'family' => 'white'],
        ['code' => 'BEI', 'name' => 'Be', 'hex' => '#D8C3A5', 'family' => 'beige'],
        ['code' => 'NAV', 'name' => 'Xanh navy', 'hex' => '#1F2A44', 'family' => 'blue'],
        ['code' => 'ROS', 'name' => 'Hồng phấn', 'hex' => '#E8B4B8', 'family' => 'pink'],
    ];

    private const PRICES = [390_000, 490_000, 590_000, 690_000, 790_000, 890_000, 990_000];

    /** Tồn đầu kỳ tại kho online theo SKU — có 0 (hết) và 3 (sắp hết) để thử storefront. */
    private const STOCK = [12, 8, 20, 3, 0, 15];

    public function __construct(
        private readonly PhotoLibrary $photos,
        private readonly CatalogImporter $catalog,
        private readonly PriceImporter $prices,
        private readonly StockImporter $stock,
        private readonly CategoryService $categories,
        private readonly CollectionService $collections,
        private readonly PriceListService $priceLists,
    ) {}

    public function run(): void
    {
        $this->ensureCategories();

        $source = (string) (config('vanishop.demo.images') ?: __DIR__.'/photos');
        $resizer = new ImageResizer((int) config('vanishop.demo.image_max', 1600));
        $temporary = storage_path('app/tmp/demo-seed-'.Str::lower((string) Str::ulid()));
        $basePrices = $onlineStock = $storeStock = [];
        $styleCodes = [];

        try {
            foreach ($this->photos->groups($source) as $index => $group) {
                $name = self::NAMES[$index % count(self::NAMES)];
                $category = self::categoryFor($name);
                $styleCode = 'VS-'.strtoupper($group['key']);
                $images = Style::query()->where('style_code', $styleCode)->exists()
                    ? [] // đã có: không thu nhỏ lại ảnh (Core chỉ thêm ảnh cho màu chưa có ảnh)
                    : array_map(fn (string $file, int $position): string => $resizer->toTemporary($file, $temporary, "{$group['key']}-{$position}"), $group['files'], array_keys($group['files']));

                $result = $this->catalog->upsertProduct(new ProductImport(
                    styleCode: $styleCode,
                    slug: Str::slug($name).'-'.$group['key'],
                    translations: [StoreLocale::default() => ['name' => $name, 'description' => "{$name} — thiết kế Vani Studio, chất liệu mềm, đứng dáng. Ảnh chụp sản phẩm thật (dữ liệu demo)."]],
                    brand: self::BRAND,
                    categories: [['slug' => $category, 'name' => self::CATEGORIES[$category][0], 'parent_slug' => 'nu'], ['slug' => self::NEW_ARRIVALS, 'name' => 'Hàng mới về']],
                    colors: [[...self::COLORS[$index % count(self::COLORS)], 'images' => $images]],
                    sizes: ['S', 'M', 'L'],
                ));
                $this->placeInCategory($styleCode, $category);
                $styleCodes[$category][] = $styleCode;

                foreach ($result->skus as $sku) {
                    $basePrices[$sku] = ['amount' => self::pick(self::PRICES, $group['key'])];
                    $onlineStock[$sku] = self::pick(self::STOCK, $sku);
                    $storeStock[$sku] = 2;
                }
            }
        } finally {
            File::deleteDirectory($temporary);
        }

        $this->prices->setBasePrices($basePrices);
        $this->salePrices($basePrices);
        $this->seedStock($onlineStock, null);
        $this->seedStock($storeStock, StoreSeeder::STORE_CODE);
        $this->seedCollections($styleCodes);
    }

    public static function categoryFor(string $name): string
    {
        foreach (self::CATEGORIES as $slug => [, $prefixes]) {
            foreach ($prefixes as $prefix) {
                if (str_starts_with($name, $prefix)) {
                    return $slug;
                }
            }
        }

        return 'dam';
    }

    private function ensureCategories(): void
    {
        $locale = StoreLocale::default();
        if (! Category::query()->where('slug', 'nu')->exists()) {
            $this->categories->create(new CategoryInput('nu', CategoryStatus::Active, [$locale => ['name' => 'Nữ', 'description' => 'Thời trang nữ Vani Studio: đầm, áo, chân váy, quần.']]));
        }
        $parent = (int) Category::query()->where('slug', 'nu')->value('id');
        $position = 0;
        foreach (self::CATEGORIES as $slug => [$name]) {
            if (! Category::query()->where('slug', $slug)->exists()) {
                $this->categories->create(new CategoryInput($slug, CategoryStatus::Active, [$locale => ['name' => $name]], $parent, $position));
            }
            $position++;
        }
        if (! Category::query()->where('slug', self::NEW_ARRIVALS)->exists()) {
            $this->categories->create(new CategoryInput(self::NEW_ARRIVALS, CategoryStatus::Active, [$locale => ['name' => 'Hàng mới về']], null, 1));
        }
    }

    /**
     * Sản phẩm nhập từ bản demo cũ chỉ có "Hàng mới về": thêm danh mục con, đặt làm danh mục chính.
     */
    private function placeInCategory(string $styleCode, string $categorySlug): void
    {
        $style = Style::query()->where('style_code', $styleCode)->first();
        $category = Category::query()->where('slug', $categorySlug)->value('id');
        if ($style === null || $category === null) {
            return;
        }
        $style->categories()->syncWithoutDetaching([(int) $category]);
        $newArrivals = Category::query()->where('slug', self::NEW_ARRIVALS)->value('id');
        if ($style->primary_category_id === null || $style->primary_category_id === $newArrivals) {
            $style->forceFill(['primary_category_id' => (int) $category])->save();
        }
    }

    /**
     * Bảng giá "Sale" (giảm ~20%) cho các SKU có mã nhóm ảnh chia hết cho 4 — thẻ sản phẩm hiện giá gạch.
     *
     * @param  array<string, array{amount: int}>  $basePrices
     */
    private function salePrices(array $basePrices): void
    {
        $list = PriceList::query()->where('code', 'sale')->first()
            ?? $this->priceLists->save(['code' => 'sale', 'name' => 'Sale cuối mùa', 'type' => 'sale', 'priority' => 10, 'starts_at' => null, 'ends_at' => null, 'status' => 'active']);
        if (DB::table('prices')->where('price_list_id', $list->id)->exists()) {
            return;
        }
        $variants = DB::table('variants')->join('styles', 'styles.id', '=', 'variants.style_id')
            ->whereIn('variants.sku', array_keys($basePrices))->get(['variants.id', 'variants.sku', 'styles.style_code']);
        $rows = [];
        foreach ($variants as $variant) {
            if (crc32((string) $variant->style_code) % 4 === 0) {
                $rows[] = ['variant_id' => (int) $variant->id, 'amount' => (int) (round($basePrices[$variant->sku]['amount'] * 0.8 / 10_000) * 10_000), 'compare_at_amount' => null];
            }
        }
        if ($rows !== []) {
            $this->priceLists->setPrices($list, $rows);
        }
    }

    /**
     * Tồn đầu kỳ chỉ cho SKU chưa có dòng tồn ở kho đó (chạy lại không xoá biến động của đơn demo).
     *
     * @param  array<string, int>  $quantities
     */
    private function seedStock(array $quantities, ?string $locationCode): void
    {
        $location = $locationCode !== null
            ? Location::query()->where('code', $locationCode)->first()
            : Location::query()->where('status', 'active')->where('ships_online_orders', true)->where('stock_authority', Location::AUTHORITY_VANISHOP)->orderByDesc('priority')->orderBy('id')->first();
        if ($location === null) {
            return;
        }
        $known = DB::table('stock_levels')->join('variants', 'variants.id', '=', 'stock_levels.variant_id')
            ->where('stock_levels.location_id', $location->id)->whereIn('variants.sku', array_keys($quantities))->pluck('variants.sku')->all();
        $missing = array_diff_key($quantities, array_flip($known));
        if ($missing !== []) {
            $this->stock->setOnHand($missing, $location->code, 'Tồn đầu kỳ (demo)');
        }
    }

    /**
     * @param  array<string, list<string>>  $styleCodes  danh mục → mã sản phẩm
     */
    private function seedCollections(array $styleCodes): void
    {
        $locale = StoreLocale::default();
        $collections = [
            'thu-dong-2026' => ['Thu Đông 2026', 'Len, dạ và lụa cho mùa se lạnh.', [...array_slice($styleCodes['ao'] ?? [], 0, 4), ...array_slice($styleCodes['dam'] ?? [], 0, 4)]],
            'cong-so-thanh-lich' => ['Công sở thanh lịch', 'Đi làm chỉn chu mà vẫn thoải mái.', [...array_slice($styleCodes['chan-vay'] ?? [], 0, 3), ...array_slice($styleCodes['quan'] ?? [], 0, 2), ...array_slice($styleCodes['ao'] ?? [], 4, 2)]],
        ];
        $position = 0;
        foreach ($collections as $slug => [$name, $description, $codes]) {
            if ($codes !== [] && ! ProductCollection::query()->where('slug', $slug)->exists()) {
                $this->collections->save(['slug' => $slug, 'status' => 'active', 'position' => $position, 'translations' => [$locale => ['name' => $name, 'description' => $description]]], $codes);
            }
            $position++;
        }
    }

    /**
     * @template T
     *
     * @param  list<T>  $values
     * @return T
     */
    private static function pick(array $values, string $seed): mixed
    {
        return $values[crc32($seed) % count($values)];
    }
}
