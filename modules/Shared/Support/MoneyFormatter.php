<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Modules\Shared\Domain\Money\Money;

/**
 * Định dạng tiền để hiển thị theo quy ước Việt Nam (1.250.000 ₫).
 */
final class MoneyFormatter
{
    /** @var array<string, string> */
    private const SYMBOLS = ['VND' => '₫', 'USD' => '$'];

    public function format(Money $money): string
    {
        $exponent = $money->currency->exponent;
        $absolute = abs($money->amount);
        $divisor = 10 ** $exponent;

        $integer = number_format(intdiv($absolute, $divisor), 0, ',', '.');
        $fraction = $exponent > 0 ? ','.str_pad((string) ($absolute % $divisor), $exponent, '0', STR_PAD_LEFT) : '';
        $sign = $money->amount < 0 ? '-' : '';
        $symbol = self::SYMBOLS[$money->currency->code] ?? $money->currency->code;

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
