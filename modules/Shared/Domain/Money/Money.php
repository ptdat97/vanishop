<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Money;

use InvalidArgumentException;
use RoundingMode;

/**
 * Số tiền bất biến: số nguyên theo minor unit + tiền tệ. Không bao giờ dùng float.
 *
 * @see docs/02-architecture/money.md
 */
final readonly class Money
{
    private const BASIS_POINTS = 10_000;

    private function __construct(
        public int $amount,
        public Currency $currency,
    ) {}

    public static function of(int $amount, Currency|string $currency): self
    {
        return new self($amount, $currency instanceof Currency ? $currency : Currency::of($currency));
    }

    public static function vnd(int $amount): self
    {
        return new self($amount, Currency::vnd());
    }

    public static function zero(Currency|string $currency): self
    {
        return self::of(0, $currency);
    }

    public function add(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount + $other->amount, $this->currency);
    }

    public function subtract(self $other): self
    {
        $this->assertSameCurrency($other);

        return new self($this->amount - $other->amount, $this->currency);
    }

    public function multiply(int $quantity): self
    {
        return new self($this->amount * $quantity, $this->currency);
    }

    public function negate(): self
    {
        return new self(-$this->amount, $this->currency);
    }

    /**
     * Tính phần trăm theo basis points (1050 = 10,50%) bằng số học nguyên.
     */
    public function percentage(int $basisPoints, RoundingMode $mode = RoundingMode::HalfAwayFromZero): self
    {
        return new self(self::divide($this->amount * $basisPoints, self::BASIS_POINTS, $mode), $this->currency);
    }

    /**
     * Phần thuế nằm trong số tiền ĐÃ GỒM thuế: round_half_up(gross × rate / (10000 + rate)), rate theo basis points.
     */
    public function includedTax(int $rateBasisPoints, RoundingMode $mode = RoundingMode::HalfAwayFromZero): self
    {
        if ($rateBasisPoints < 0) {
            throw new InvalidArgumentException('Thuế suất không được âm.');
        }

        return new self(self::divide($this->amount * $rateBasisPoints, self::BASIS_POINTS + $rateBasisPoints, $mode), $this->currency);
    }

    /**
     * Làm tròn đến bội số của $step (ví dụ 1.000 ₫).
     */
    public function roundToStep(int $step, RoundingMode $mode = RoundingMode::HalfAwayFromZero): self
    {
        if ($step < 1) {
            throw new InvalidArgumentException('Bước làm tròn phải >= 1.');
        }

        return new self(self::divide($this->amount, $step, $mode) * $step, $this->currency);
    }

    /**
     * Chia theo trọng số bằng phương pháp largest remainder: tổng các phần luôn bằng số gốc.
     *
     * @param  list<int>  $weights
     * @return list<self>
     */
    public function allocate(array $weights): array
    {
        if ($weights === []) {
            throw new InvalidArgumentException('Cần ít nhất một trọng số.');
        }

        foreach ($weights as $weight) {
            if ($weight < 0) {
                throw new InvalidArgumentException('Trọng số không được âm.');
            }
        }

        $totalWeight = array_sum($weights);
        if ($totalWeight === 0) {
            throw new InvalidArgumentException('Tổng trọng số phải lớn hơn 0.');
        }

        $sign = $this->amount < 0 ? -1 : 1;
        $absolute = abs($this->amount);

        $parts = [];
        $remainders = [];
        foreach ($weights as $index => $weight) {
            $numerator = $absolute * $weight;
            $parts[$index] = intdiv($numerator, $totalWeight);
            $remainders[$index] = $numerator % $totalWeight;
        }

        $leftover = $absolute - array_sum($parts);

        $order = array_keys($remainders);
        usort($order, fn (int $a, int $b): int => [$remainders[$b], $a] <=> [$remainders[$a], $b]);

        for ($i = 0; $i < $leftover; $i++) {
            $parts[$order[$i]]++;
        }

        return array_map(fn (int $part): self => new self($sign * $part, $this->currency), $parts);
    }

    public function isZero(): bool
    {
        return $this->amount === 0;
    }

    public function isNegative(): bool
    {
        return $this->amount < 0;
    }

    public function isPositive(): bool
    {
        return $this->amount > 0;
    }

    public function equals(self $other): bool
    {
        return $this->currency->equals($other->currency) && $this->amount === $other->amount;
    }

    public function greaterThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount > $other->amount;
    }

    public function lessThan(self $other): bool
    {
        $this->assertSameCurrency($other);

        return $this->amount < $other->amount;
    }

    private function assertSameCurrency(self $other): void
    {
        if (! $this->currency->equals($other->currency)) {
            throw new CurrencyMismatch($this->currency, $other->currency);
        }
    }

    /**
     * Chia nguyên có làm tròn, không dùng float.
     */
    private static function divide(int $numerator, int $denominator, RoundingMode $mode): int
    {
        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        if ($remainder === 0) {
            return $quotient;
        }

        $direction = ($numerator < 0) !== ($denominator < 0) ? -1 : 1;
        $twiceRemainder = abs($remainder) * 2;
        $absDenominator = abs($denominator);

        $roundAway = match ($mode) {
            RoundingMode::TowardsZero => false,
            RoundingMode::AwayFromZero => true,
            RoundingMode::HalfAwayFromZero => $twiceRemainder >= $absDenominator,
            RoundingMode::HalfTowardsZero => $twiceRemainder > $absDenominator,
            RoundingMode::HalfEven => $twiceRemainder > $absDenominator
                || ($twiceRemainder === $absDenominator && $quotient % 2 !== 0),
            default => throw new InvalidArgumentException("Chế độ làm tròn [{$mode->name}] chưa được hỗ trợ."),
        };

        return $roundAway ? $quotient + $direction : $quotient;
    }
}
