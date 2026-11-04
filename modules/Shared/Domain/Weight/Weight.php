<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Weight;

use InvalidArgumentException;

/**
 * Khối lượng (reference-parity §3.1-2): lưu gram nguyên để phí ship không dính số thực. Đơn vị khác chỉ dùng lúc nhập
 * liệu (`of(1.2, 'kg')`), làm tròn tới gram gần nhất.
 */
final readonly class Weight
{
    /** Gram mỗi đơn vị nhập liệu được nhận. */
    public const UNITS = ['g' => 1.0, 'kg' => 1000.0, 'lb' => 453.59237, 'oz' => 28.349523125];

    private function __construct(public int $grams) {}

    public static function grams(int $grams): self
    {
        if ($grams < 0) {
            throw new InvalidArgumentException('Khối lượng không được âm.');
        }

        return new self($grams);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function of(float|int|string $value, string $unit = 'g'): self
    {
        $factor = self::UNITS[strtolower($unit)] ?? throw new InvalidArgumentException("Đơn vị khối lượng [{$unit}] không hỗ trợ.");
        $number = is_string($value) ? str_replace(',', '.', trim($value)) : $value;
        if (! is_numeric($number)) {
            throw new InvalidArgumentException("Khối lượng [{$value}] không hợp lệ.");
        }

        return self::grams((int) round((float) $number * $factor));
    }

    public function add(self $other): self
    {
        return new self($this->grams + $other->grams);
    }

    public function multiply(int $quantity): self
    {
        if ($quantity < 0) {
            throw new InvalidArgumentException('Số lượng không được âm.');
        }

        return new self($this->grams * $quantity);
    }

    public function kilograms(): float
    {
        return $this->grams / 1000;
    }

    public function isZero(): bool
    {
        return $this->grams === 0;
    }
}
