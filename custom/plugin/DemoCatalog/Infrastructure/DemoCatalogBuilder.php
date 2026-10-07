<?php

declare(strict_types=1);

namespace Plugin\DemoCatalog\Infrastructure;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Catalog\Contracts\CatalogImporter;
use Modules\Catalog\Contracts\Data\ProductImport;
use Modules\Inventory\Contracts\StockImporter;
use Modules\Pricing\Contracts\PriceImporter;

/**
 * Dựng catalog demo từ ảnh chụp: mỗi nhóm ảnh → một sản phẩm (thương hiệu "Vani Studio", danh mục "Hàng mới về"),
 * một màu, size S/M/L, giá niêm yết và tồn đầu kỳ. Giá trị demo suy ra từ mã nhóm ảnh (crc32) → chạy lại cho cùng kết
 * quả. Chỉ dùng contract công khai của Core (CatalogImporter, PriceImporter, StockImporter).
 */
final class DemoCatalogBuilder
{
    private const BRAND = ['slug' => 'vani-studio', 'code' => 'VS', 'name' => 'Vani Studio'];

    private const CATEGORY = ['slug' => 'hang-moi-ve', 'name' => 'Hàng mới về'];

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

    private const STOCK = [12, 8, 20, 3, 0, 15];

    public function __construct(
        private readonly PhotoLibrary $photos,
        private readonly CatalogImporter $catalog,
        private readonly PriceImporter $prices,
        private readonly StockImporter $stock,
    ) {}

    /**
     * Kế hoạch (không ghi gì): nhóm ảnh → sản phẩm.
     *
     * @return list<array{key: string, style_code: string, name: string, images: int, price: int}>
     */
    public function plan(string $source, ?int $limit = null): array
    {
        $groups = $this->photos->groups($source);
        $groups = $limit === null ? $groups : array_slice($groups, 0, $limit);

        return array_map(fn (array $group, int $index): array => [
            'key' => $group['key'], 'style_code' => $this->styleCode($group['key']), 'name' => self::NAMES[$index % count(self::NAMES)],
            'images' => count($group['files']), 'price' => $this->pick(self::PRICES, $group['key']),
        ], $groups, array_keys($groups));
    }

    /**
     * @return array{products: int, created: int, images: int, skus: int, stock_location: string|null}
     */
    public function build(string $source, ImageResizer $resizer, ?int $limit = null, ?callable $progress = null): array
    {
        $groups = $this->photos->groups($source);
        if ($limit !== null) {
            $groups = array_slice($groups, 0, $limit);
        }

        $temporary = storage_path('app/tmp/demo-catalog-'.Str::lower((string) Str::ulid()));
        $summary = ['products' => 0, 'created' => 0, 'images' => 0, 'skus' => 0, 'stock_location' => null];
        $prices = $stock = [];

        try {
            foreach ($groups as $index => $group) {
                $name = self::NAMES[$index % count(self::NAMES)];
                $color = self::COLORS[$index % count(self::COLORS)];
                $images = array_map(fn (string $file, int $position): string => $resizer->toTemporary($file, $temporary, "{$group['key']}-{$position}"), $group['files'], array_keys($group['files']));

                $result = $this->catalog->upsertProduct(new ProductImport(
                    styleCode: $this->styleCode($group['key']),
                    slug: Str::slug($name).'-'.$group['key'],
                    translations: ['vi' => ['name' => $name, 'description' => 'Sản phẩm demo — ảnh chụp thật từ bộ sưu tập Vani.']],
                    brand: self::BRAND,
                    categories: [self::CATEGORY],
                    colors: [[...$color, 'images' => $images]],
                    sizes: ['S', 'M', 'L'],
                ));

                foreach ($result->skus as $sku) {
                    $prices[$sku] = ['amount' => $this->pick(self::PRICES, $group['key'])];
                    $stock[$sku] = $this->pick(self::STOCK, $sku);
                }
                $summary['products']++;
                $summary['created'] += $result->created ? 1 : 0;
                $summary['images'] += $result->imagesAdded;
                $summary['skus'] += count($result->skus);
                if ($progress !== null) {
                    $progress($group['key'], $name, $result->created, $result->imagesAdded);
                }
            }

            $this->prices->setBasePrices($prices);
            $summary['stock_location'] = $this->stock->setOnHand($stock, null, 'Tồn đầu kỳ (demo)')['location'];
        } finally {
            File::deleteDirectory($temporary);
        }

        return $summary;
    }

    private function styleCode(string $key): string
    {
        return 'VS-'.strtoupper($key);
    }

    /**
     * @template T
     *
     * @param  list<T>  $values
     * @return T
     */
    private function pick(array $values, string $seed): mixed
    {
        return $values[crc32($seed) % count($values)];
    }
}
