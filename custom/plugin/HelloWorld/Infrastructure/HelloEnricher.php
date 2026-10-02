<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Implementation tham chiếu của StorefrontEnricher (R26): thêm lời chào vào dữ liệu PDP.
 * Native storefront và Storefront API cùng nhận `extensions["vani.hello-world"].greeting`.
 */
final class HelloEnricher implements StorefrontEnricher
{
    public function resource(): string
    {
        return 'product';
    }

    public function enrich(array $items, string $locale): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[$item['id']] = ['greeting' => $locale === 'en' ? "Hello from {$item['name']}!" : "Xin chào từ {$item['name']}!"];
        }

        return $result;
    }
}
