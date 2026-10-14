<?php

declare(strict_types=1);

namespace Plugin\ShippingFlatRate\Infrastructure;

use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Giỏ (native + Storefront API) có `extensions["vani.shipping-flat-rate"].free_shipping` — tiến độ theo tạm tính giỏ.
 */
final class CartProgressEnricher implements StorefrontEnricher
{
    public function __construct(private readonly FreeShippingProgress $progress) {}

    public function resource(): string
    {
        return 'cart';
    }

    public function enrich(array $items, string $locale): array
    {
        $result = [];
        foreach ($items as $cart) {
            $subtotal = $cart['subtotal'] ?? null;
            $progress = is_array($subtotal) ? $this->progress->forAmount((int) $subtotal['amount'], (string) $subtotal['currency']) : null;
            if ($progress !== null) {
                $result[$cart['id']] = ['free_shipping' => $progress];
            }
        }

        return $result;
    }
}
