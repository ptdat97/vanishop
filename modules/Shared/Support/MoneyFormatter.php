<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Modules\Shared\Domain\Money\Money;

/**
 * Định dạng tiền để hiển thị theo cấu hình cửa hàng `vanishop.locale.money` (VN: 1.250.000 ₫). Container dựng từ cấu
 * hình (`fromConfig`); khởi tạo trực tiếp dùng mặc định trung lập (1,250,000 VND).
 */
final class MoneyFormatter
{
    /**
     * @param  array<string, string>  $symbols  mã tiền tệ => ký hiệu; thiếu → in mã
     */
    public function __construct(
        private readonly string $thousandsSeparator = ',',
        private readonly string $decimalSeparator = '.',
        private readonly array $symbols = [],
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('vanishop.locale.money.thousands_separator', ','),
            (string) config('vanishop.locale.money.decimal_separator', '.'),
            array_map('strval', (array) config('vanishop.locale.money.symbols', [])),
        );
    }

    public function formatAmount(int $amount, string $currency): string
    {
        return $this->format(Money::of($amount, $currency));
    }

    public function format(Money $money): string
    {
        $exponent = $money->currency->exponent;
        $absolute = abs($money->amount);
        $divisor = 10 ** $exponent;

        $integer = number_format(intdiv($absolute, $divisor), 0, '', $this->thousandsSeparator);
        $fraction = $exponent > 0 ? $this->decimalSeparator.str_pad((string) ($absolute % $divisor), $exponent, '0', STR_PAD_LEFT) : '';
        $sign = $money->amount < 0 ? '-' : '';
        $symbol = $this->symbols[$money->currency->code] ?? $money->currency->code;

        return "{$sign}{$integer}{$fraction} {$symbol}";
    }

    /**
     * @return array{amount: int, currency: string, formatted: string}
     */
    public function toArray(Money $money): array
    {
        return [
            'amount' => $money->amount,
            'currency' => $money->currency->code,
            'formatted' => $this->format($money),
        ];
    }
}
