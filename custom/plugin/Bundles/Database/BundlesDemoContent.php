<?php

declare(strict_types=1);

namespace Plugin\Bundles\Database;

use Database\Seeders\Demo\DemoCatalog;
use Database\Seeders\Demo\DemoContent;
use Modules\Catalog\Persistence\Models\Style;
use Plugin\Bundles\Application\Bundles;
use Plugin\Bundles\Persistence\Bundle;

/** Combo demo: "Set công sở" (áo + chân váy, giảm 10%) và "Set dạo phố 3 món" (áo + quần + đầm, giảm 15%). */
final class BundlesDemoContent implements DemoContent
{
    public function __construct(private readonly Bundles $bundles) {}

    public function seed(): void
    {
        $sets = [
            'set-cong-so' => ['Set công sở', 'Áo và chân váy phối sẵn, mặc đi làm cả tuần.', 10, ['ao', 'chan-vay']],
            'set-dao-pho-3-mon' => ['Set dạo phố 3 món', 'Đủ bộ cho cuối tuần: áo, quần và một chiếc đầm.', 15, ['ao', 'quan', 'dam']],
        ];
        $covers = DemoCatalog::byStyle();
        $position = 0;
        foreach ($sets as $slug => [$name, $description, $percent, $categories]) {
            if (Bundle::query()->where('slug', $slug)->exists()) {
                continue;
            }
            $items = [];
            foreach ($categories as $index => $category) {
                $style = $this->styleIn($category, $position + $index);
                if ($style !== null) {
                    $items[] = ['style_id' => $style->id, 'quantity' => 1];
                }
            }
            if (count($items) !== count($categories)) {
                continue;
            }
            $cover = $covers[Style::query()->whereKey($items[0]['style_id'])->value('style_code')][0] ?? null;
            $this->bundles->save([
                'slug' => $slug, 'name' => $name, 'description' => $description, 'image_media_id' => $cover,
                'discount_percent' => $percent, 'active' => true, 'starts_at' => null, 'ends_at' => null,
                'position' => $position++, 'items' => $items,
            ]);
        }
    }

    public function links(): array
    {
        return [];
    }

    private function styleIn(string $category, int $offset): ?Style
    {
        $categoryId = DemoCatalog::categoryId($category);

        return $categoryId === null ? null : Style::query()->where('style_code', 'like', 'VS-%')->where('status', 'active')
            ->where('primary_category_id', $categoryId)->orderBy('id')->skip($offset % 2)->first();
    }
}
