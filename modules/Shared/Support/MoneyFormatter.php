<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Modules\Shared\Domain\Money\Currency;
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
        /** @var array<string, list<string>> ngôn ngữ => đơn vị [nghìn, triệu, tỷ] cho số rút gọn */
        private readonly array $compactUnits = [],
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            (string) config('vanishop.locale.money.thousands_separator', ','),
            (string) config('vanishop.locale.money.decimal_separator', '.'),
            array_map('strval', (array) config('vanishop.locale.money.symbols', [])),
            array_map(fn (mixed $units): array => array_values(array_map('strval', (array) $units)), (array) config('vanishop.locale.compact_units', [])),
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
     * Số rút gọn cho dashboard (reference-parity §3.1-1): 1 250 → "1,3 N", 2 400 000 → "2,4 Tr", 1 200 000 000 → "1,2 Tỷ";
     * một chữ số thập phân (bỏ ",0"), dưới 1 000 giữ nguyên. Đơn vị theo ngôn ngữ (`vanishop.locale.compact_units`),
     * ngôn ngữ chưa khai báo → K/M/B.
     */
    public function compact(int|float $value, string $locale): string
    {
        $units = $this->compactUnits[$locale] ?? ['K', 'M', 'B'];
        $absolute = abs($value);
        $sign = $value < 0 ? '-' : '';
        foreach ([[1_000_000_000, 2], [1_000_000, 1], [1_000, 0]] as [$step, $unit]) {
            // So sau khi làm tròn: 999 950 → "1 Tr", không phải "1.000 N". Dưới 1 000 luôn giữ nguyên.
            $rounded = round($absolute / $step, 1);
            if ($absolute >= $step || ($rounded >= 1 && $step > 1_000)) {
                $text = number_format($rounded, fmod($rounded, 1.0) === 0.0 ? 0 : 1, $this->decimalSeparator, $this->thousandsSeparator);

                return "{$sign}{$text} ".($units[$unit] ?? '');
            }
        }

        return $sign.number_format($absolute, fmod((float) $absolute, 1.0) === 0.0 ? 0 : 1, $this->decimalSeparator, $this->thousandsSeparator);
    }

    /**
     * Tiền rút gọn: "2,4 Tr ₫" (tính theo đơn vị tiền chính, không theo đơn vị nhỏ nhất).
     */
    public function compactMoney(Money $money, string $locale): string
    {
        $symbol = $this->symbols[$money->currency->code] ?? $money->currency->code;

        return $this->compact($money->amount / (10 ** $money->currency->exponent), $locale).' '.$symbol;
    }

    /**
     * Quy tắc định dạng cho client (Admin `MoneyText` định dạng giống hệt `format()`).
     *
     * @return array{currency: string, exponent: int, thousandsSeparator: string, decimalSeparator: string, symbol: string, compactUnits: list<string>}
     */
    public function rules(string $currency, ?string $locale = null): array
    {
        $currency = Currency::of($currency);

        return [
            'currency' => $currency->code,
            'exponent' => $currency->exponent,
            'thousandsSeparator' => $this->thousandsSeparator,
            'decimalSeparator' => $this->decimalSeparator,
            'symbol' => $this->symbols[$currency->code] ?? $currency->code,
            'compactUnits' => $this->compactUnits[$locale ?? ''] ?? array_values($this->compactUnits)[0] ?? ['K', 'M', 'B'],
        ];
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
