<?php

declare(strict_types=1);

namespace Modules\Storefront\Tests\Feature\Fixtures;

use Modules\Storefront\Contracts\StorefrontEnricher;
use RuntimeException;

/** Gắn nhãn cho mọi thẻ sản phẩm; đếm số lần gọi để kiểm tra batch. Thêm cả id lạ (Core phải bỏ). */
final class BadgeEnricher implements StorefrontEnricher
{
    public static int $calls = 0;

    public function resource(): string
    {
        return 'product_card';
    }

    public function enrich(array $items, string $locale): array
    {
        self::$calls++;
        $result = ['khong-co-trong-danh-sach' => ['badge' => 'x']];
        foreach ($items as $item) {
            $result[$item['id']] = ['badge' => 'Mới'];
        }

        return $result;
    }
}

final class BrokenEnricher implements StorefrontEnricher
{
    public function resource(): string
    {
        return 'product_card';
    }

    public function enrich(array $items, string $locale): array
    {
        throw new RuntimeException('plugin hỏng');
    }
}

final class CartNoteEnricher implements StorefrontEnricher
{
    public function resource(): string
    {
        return 'cart';
    }

    public function enrich(array $items, string $locale): array
    {
        return [$items[0]['id'] => ['freeship_remaining' => max(0, 500_000 - $items[0]['subtotal']['amount'])]];
    }
}
