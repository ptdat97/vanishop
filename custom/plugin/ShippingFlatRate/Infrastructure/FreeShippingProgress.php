<?php

declare(strict_types=1);

namespace Plugin\ShippingFlatRate\Infrastructure;

use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;
use Plugin\ShippingFlatRate\ShippingFlatRateServiceProvider;

/**
 * Thanh tiến độ miễn phí giao: dùng **cùng ngưỡng** `free_over` với phí giao (FlatRateShipping), một nguồn số liệu.
 * Đặc tả: docs/05-plugin/specs/free-shipping-progress.md.
 */
final class FreeShippingProgress
{
    public function __construct(
        private readonly Settings $settings,
        private readonly MoneyFormatter $money,
    ) {}

    public function threshold(): ?int
    {
        $freeOver = $this->setting('free_over') ?? config('vani.shipping-flat-rate.free_over');

        return $freeOver === null || $freeOver === '' ? null : (int) $freeOver;
    }

    public function enabled(): bool
    {
        return (bool) ($this->setting('progress_enabled') ?? true) && $this->threshold() !== null;
    }

    public function shownOn(string $position): bool
    {
        $defaults = ['cart' => true, 'checkout' => true, 'pdp' => false];

        return $this->enabled() && (bool) ($this->setting("progress_on_{$position}") ?? $defaults[$position] ?? false);
    }

    /**
     * Tiến độ cho số tiền hàng (sau giảm giá, chưa gồm phí giao) — null khi tắt, chưa cấu hình ngưỡng hoặc giỏ trống.
     *
     * @return array{threshold: array{amount: int, currency: string, formatted: string}, remaining: array{amount: int, currency: string, formatted: string}, percent: int, achieved: bool, message: string, color: string|null}|null
     */
    public function forAmount(int $merchandise, string $currency): ?array
    {
        $threshold = $this->threshold();
        if (! $this->enabled() || $threshold === null || $threshold <= 0 || $merchandise <= 0) {
            return null;
        }

        $remaining = max(0, $threshold - $merchandise);
        $achieved = $remaining === 0;
        $template = $achieved
            ? $this->localized('progress_message_achieved', __('vani-shipping-flat-rate::messages.progress_achieved'))
            : $this->localized('progress_message_pending', __('vani-shipping-flat-rate::messages.progress_pending'));

        return [
            'threshold' => $this->money->toArray(Money::of($threshold, $currency)),
            'remaining' => $this->money->toArray(Money::of($remaining, $currency)),
            'percent' => min(100, (int) floor($merchandise * 100 / $threshold)),
            'achieved' => $achieved,
            'message' => str_replace('{amount}', $this->money->formatAmount($remaining, $currency), $template),
            'color' => $this->setting('progress_color'),
        ];
    }

    private function localized(string $key, string $default): string
    {
        $value = $this->setting($key.'.'.app()->getLocale()) ?? $this->setting($key.'.'.StoreLocale::default());

        return is_string($value) && trim($value) !== '' ? $value : $default;
    }

    private function setting(string $key): mixed
    {
        return $this->settings->get(ShippingFlatRateServiceProvider::ID, $key);
    }
}
