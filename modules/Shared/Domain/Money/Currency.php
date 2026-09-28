<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Money;

/**
 * Tiền tệ ISO 4217 cùng số chữ số phần lẻ (minor unit exponent).
 */
final readonly class Currency
{
    /** @var array<string, int> */
    private const EXPONENTS = [
        'VND' => 0,
        'USD' => 2,
    ];

    private function __construct(
        public string $code,
        public int $exponent,
    ) {}

    public static function of(string $code): self
    {
        $code = strtoupper($code);

        if (! array_key_exists($code, self::EXPONENTS)) {
            throw new UnsupportedCurrency($code);
        }

        return new self($code, self::EXPONENTS[$code]);
    }

    public static function vnd(): self
    {
        return self::of('VND');
    }

    public function equals(self $other): bool
    {
        return $this->code === $other->code;
    }
}
