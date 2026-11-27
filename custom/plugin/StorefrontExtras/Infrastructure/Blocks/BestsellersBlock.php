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
        return __('vani-storefront-extras::texts.bestsellers_block.best_sellers');
    }

    public function fields(): array
    {
        return [
            FieldDefinition::string('title', __('vani-storefront-extras::texts.bestsellers_block.title'), max: 120),
            FieldDefinition::int('limit', __('vani-storefront-extras::texts.bestsellers_block.number_products_1_24'), help: __('vani-storefront-extras::texts.bestsellers_block.default_8')),
            FieldDefinition::select('days', __('vani-storefront-extras::texts.bestsellers_block.calculated_over'), ['7' => __('vani-storefront-extras::texts.bestsellers_block.text_7_days'), '30' => __('vani-storefront-extras::texts.bestsellers_block.text_30_days'), '90' => __('vani-storefront-extras::texts.bestsellers_block.text_90_days')]),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        $limit = max(1, min(24, (int) ($config['limit'] ?? 8) ?: 8));
        $days = in_array((string) ($config['days'] ?? '30'), ['7', '30', '90'], true) ? (int) $config['days'] : 30;

        // Tươi 1 giờ, sau đó trả bản cũ trong lúc tính lại nền (chống dồn tải, §6b L59).
        $ids = Cache::flexible("vani-extras:bestsellers:{$days}", [3600, 7200], fn (): array => array_column(
            $this->statistics->topProducts(new DateTimeImmutable("-{$days} days"), new DateTimeImmutable, 60),
            'product_id',
        ), ['seconds' => 60]);
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
