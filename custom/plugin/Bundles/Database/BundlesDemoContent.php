<?php

declare(strict_types=1);

namespace Plugin\Bundles\Database;

use Database\Seeders\Demo\DemoCatalog;
use Database\Seeders\Demo\DemoContent;
use Plugin\Bundles\Application\Bundles;
use Plugin\Bundles\Persistence\Bundle;

/** Combo demo dùng sản phẩm và ảnh có sẵn của Vani Studio. */
final class BundlesDemoContent implements DemoContent
{
    public function __construct(private readonly Bundles $bundles) {}

    public function seed(): void
    {
        $sets = [
            ['set-cong-so', 'Set công sở', 'Sơ mi lụa cổ đức phối chân váy chữ A.', 10, ['VS-DSC6826', 'VS-DSC7406']],
            ['set-dao-pho-3-mon', 'Set dạo phố 3 món', 'Áo tay bồng, quần culottes và cardigan cho cuối tuần.', 15, ['VS-DTT9540', 'VS-VNQ9889', 'VS-VNQ7102']],
        ];
        $covers = DemoCatalog::byProduct();
        foreach ($sets as $position => [$slug, $name, $description, $percent, $codes]) {
            if (Bundle::query()->where('slug', $slug)->exists()) {
                continue;
            }
            $ids = DemoCatalog::activeProductIdsByCode($codes);
            if (count($ids) !== count($codes)) {
                continue;
            }
            $items = array_map(fn (string $code): array => ['product_id' => $ids[$code], 'quantity' => 1], $codes);
            $this->bundles->save([
                'slug' => $slug, 'name' => $name, 'description' => $description, 'image_media_id' => $covers[$codes[0]][0] ?? null,
                'discount_percent' => $percent, 'active' => true, 'starts_at' => null, 'ends_at' => null,
                'position' => $position, 'items' => $items,
            ]);
        }
    }

    public function links(): array
    {
        return [];
    }
}
