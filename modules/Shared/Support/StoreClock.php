<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Illuminate\Support\Carbon;

/**
 * Giờ của cửa hàng: múi giờ và định dạng hiển thị lấy từ `vanishop.locale.timezone|formats` — Core không ghi cứng
 * múi giờ hay định dạng của thị trường nào. DB lưu UTC; chỉ đổi múi giờ khi hiển thị/nhập liệu.
 */
final class StoreClock
{
    /** Định dạng của `<input type="datetime-local">` (chuẩn HTML, không theo thị trường). */
    public const INPUT_FORMAT = 'Y-m-d\TH:i';

    public static function timezone(): string
    {
        return (string) config('vanishop.locale.timezone', 'UTC');
    }

    public static function zone(): DateTimeZone
    {
        return new DateTimeZone(self::timezone());
    }

    /**
     * Hiển thị theo định dạng cấu hình `formats.<kind>` (`date`, `datetime`, `datetime_seconds`, `short_datetime`).
     * Nhận chuỗi thời điểm (UTC, như cột DB đọc qua query builder). null → null.
     */
    public static function format(DateTimeInterface|string|null $at, string $kind = 'datetime'): ?string
    {
        $local = self::local($at);

        return $local?->format((string) config("vanishop.locale.formats.{$kind}", 'Y-m-d H:i'));
    }

    /**
     * Giá trị cho ô nhập `datetime-local` theo giờ cửa hàng.
     */
    public static function toInput(DateTimeInterface|string|null $at): ?string
    {
        return self::local($at)?->format(self::INPUT_FORMAT);
    }

    /**
     * Giờ cửa hàng người dùng nhập → UTC để lưu.
     */
    public static function fromInput(string $local): Carbon
    {
        return Carbon::parse($local, self::timezone())->utc();
    }

    public static function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', self::zone());
    }

    private static function local(DateTimeInterface|string|null $at): ?CarbonImmutable
    {
        if ($at === null || $at === '') {
            return null;
        }

        return ($at instanceof DateTimeInterface ? CarbonImmutable::instance($at) : CarbonImmutable::parse($at, 'UTC'))->setTimezone(self::zone());
    }
}
