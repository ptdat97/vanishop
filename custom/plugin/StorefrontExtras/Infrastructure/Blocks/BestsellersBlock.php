<?php

declare(strict_types=1);

namespace Plugin\StorefrontExtras\Infrastructure\Blocks;

use DateTimeImmutable;
use Illuminate\Support\Facades\Cache;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Ordering\Contracts\OrderStatistics;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\StorefrontExtras\Infrastructure\Products;

/**
 * Bán chạy (đặc tả §3.1): xếp theo số lượng đã bán trong N ngày (đơn huỷ/dòng huỷ không tính), chỉ sản phẩm còn bán;
 * thiếu dữ liệu → bù sản phẩm mới nhất. Danh sách id cache 1 giờ (không phụ thuộc khách).
 */
final class BestsellersBlock implements StorefrontBlock
{
    public function __construct(
        private readonly OrderStatistics $statistics,
        private readonly Products $products,
    ) {}

    public function type(): string
    {
        return 'extras_bestsellers';
    }

    public function label(): string
    {
        return 'Bán chạy';
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', 'Tiêu đề', max: 120),
            FieldDefinition::int('limit', 'Số sản phẩm (1–24)', help: 'Mặc định 8.'),
            FieldDefinition::select('days', 'Tính trong', ['7' => '7 ngày', '30' => '30 ngày', '90' => '90 ngày']),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 8) ?: 8));
        $days = in_array((string) ($config['days'] ?? '30'), ['7', '30', '90'], true) ? (int) $config['days'] : 30;

        $ids = Cache::remember("vani-extras:bestsellers:{$days}", 3600, fn (): array => array_column(
            $this->statistics->topStyles(new DateTimeImmutable("-{$days} days"), new DateTimeImmutable, 60),
            'style_id',
        ));
        $products = array_slice($this->products->byIds(array_slice($ids, 0, 60), $locale), 0, $limit);
        if (count($products) < $limit) {
            $taken = array_column($products, 'id');
            foreach ($this->products->from('newest', '', $limit + count($taken), $locale) as $card) {
                if (count($products) < $limit && ! in_array($card['id'], $taken, true)) {
                    $products[] = $card;
                }
            }
        }

        return ['title' => (string) ($config['title'] ?? ''), 'products' => $products];
    }

    public function view(): string
    {
        return 'vani-storefront-extras::blocks.products';
    }
}
