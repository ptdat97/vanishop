<?php

declare(strict_types=1);

namespace Plugin\TrackingPixels\Infrastructure;

use Modules\Shared\Domain\Money\Currency;

/**
 * Dữ liệu sự kiện thương mại điện tử theo dạng GA4 (`items`, `value`, `currency`…) dựng từ dữ liệu storefront của Core.
 * Tiền trong dữ liệu Core là số nguyên đơn vị nhỏ nhất (`amount`); nền tảng quảng cáo nhận đơn vị chính.
 */
final class Payloads
{
    /** Số sản phẩm tối đa đưa vào sự kiện danh sách. */
    public const LIST_LIMIT = 30;

    /**
     * @param  array<string, mixed>  $product  ProductViews::detail
     * @return array<string, mixed>
     */
    public static function viewItem(array $product): array
    {
        $variant = collect((array) ($product['variants'] ?? []))->firstWhere('id', $product['default_variant_id'] ?? null) ?? ($product['variants'][0] ?? null);
        $amount = $variant['price']['amount'] ?? $product['price']['min'] ?? null;
        $item = self::item((string) ($variant['sku'] ?? $product['style_code'] ?? $product['id']), (string) $product['name'], $product['brand']['name'] ?? null, $amount, 1);

        return ['currency' => $amount['currency'] ?? null, 'value' => $item['price'], 'items' => [$item]];
    }

    /**
     * @param  array{type: string, category: array<string, mixed>|null, brand: array<string, mixed>|null}  $context
     * @param  array<string, mixed>  $listing  ProductViews::listing
     * @return array<string, mixed>
     */
    public static function viewItemList(array $context, array $listing): array
    {
        $name = $context['category']['name'] ?? $context['brand']['name'] ?? 'search';
        $items = [];
        foreach (array_slice($listing['items'], 0, self::LIST_LIMIT) as $index => $card) {
            $items[] = [...self::item((string) ($card['style_code'] ?? $card['id']), (string) $card['name'], $card['brand']['name'] ?? null, $card['price']['min'] ?? null, 1), 'index' => $index];
        }

        return ['item_list_name' => $name, 'items' => $items];
    }

    /**
     * @param  array<string, mixed>  $quote  CheckoutPresenter::quote
     * @return array<string, mixed>
     */
    public static function beginCheckout(array $quote): array
    {
        $items = array_map(fn (array $line): array => self::item((string) $line['sku'], (string) $line['name'], null, $line['unit_price'], (int) $line['quantity']), $quote['lines']);

        return ['currency' => $quote['currency'], 'value' => self::major($quote['total']), 'items' => $items];
    }

    /**
     * @param  array<string, mixed>  $order  OrderPresenter::present
     * @return array<string, mixed>
     */
    public static function purchase(array $order): array
    {
        $items = array_map(fn (array $line): array => self::item((string) $line['sku'], (string) $line['name'], null, $line['unit_price'], (int) $line['quantity']), $order['lines']);

        return [
            'transaction_id' => (string) $order['number'],
            'event_id' => self::purchaseEventId((string) $order['id']),
            'currency' => $order['total']['currency'] ?? null,
            'value' => self::major($order['total']),
            'tax' => self::major($order['tax_included']),
            'shipping' => self::major($order['shipping_fee']),
            'coupon' => implode(',', $order['pricing']['vouchers'] ?? []),
            'payment_type' => (string) $order['payment_method'],
            'items' => $items,
        ];
    }

    /**
     * Mã sự kiện mua dùng chung trình duyệt và server (Meta khử trùng theo mã này).
     */
    public static function purchaseEventId(string $orderPublicId): string
    {
        return 'purchase-'.$orderPublicId;
    }

    /**
     * @param  array{amount: int, currency: string}|null  $price
     * @return array{item_id: string, item_name: string, item_brand?: string, price: float|int, quantity: int}
     */
    public static function item(string $id, string $name, ?string $brand, ?array $price, int $quantity): array
    {
        return array_filter(['item_id' => $id, 'item_name' => $name, 'item_brand' => $brand, 'price' => self::major($price), 'quantity' => $quantity], fn ($value): bool => $value !== null);
    }

    /**
     * @param  array{amount: int, currency: string}|null  $money
     */
    public static function major(?array $money): float|int
    {
        if ($money === null) {
            return 0;
        }
        $exponent = Currency::of((string) $money['currency'])->exponent;

        return $exponent === 0 ? (int) $money['amount'] : round($money['amount'] / 10 ** $exponent, $exponent);
    }
}
