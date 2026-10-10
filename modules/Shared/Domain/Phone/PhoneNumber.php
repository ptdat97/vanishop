<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Phone;

/**
 * Số điện thoại đã chuẩn hoá: E.164 + dạng hiển thị trong nước. Luật đọc chuỗi người dùng nhập thuộc thị trường —
 * dựng qua `Modules\Shared\Support\Phones` (extension point PhoneNumberPolicy), không đoán ở đây.
 */
final readonly class PhoneNumber
{
    private function __construct(
        public string $e164,
        private string $national,
    ) {}

    /**
     * @throws InvalidPhoneNumber khi `$e164` không đúng dạng E.164
     */
    public static function of(string $e164, ?string $national = null): self
    {
        if (preg_match('/^\+[1-9]\d{6,14}$/', $e164) !== 1) {
            throw new InvalidPhoneNumber($e164);
        }

        return new self($e164, $national ?? $e164);
    }

    /**
     * Dạng hiển thị trong nước (vd. VN: 0912345678).
     */
    public function national(): string
    {
        return $this->national;
    }

    /**
     * Dạng che cho log/danh sách: 091****678.
     */
    public function masked(): string
    {
        $national = $this->national;

        return substr($national, 0, 3).str_repeat('*', max(0, strlen($national) - 6)).substr($national, -3);
    }

    public function equals(self $other): bool
    {
        return $this->e164 === $other->e164;
    }
}
