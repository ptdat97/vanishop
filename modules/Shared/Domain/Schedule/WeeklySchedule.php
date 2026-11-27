<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Schedule;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Lịch lặp theo tuần (0.5.116): tối đa {@see MAX_WINDOWS} khung, mỗi khung = các ngày trong tuần (ISO 1 = thứ Hai … 7 = Chủ
 * nhật) + giờ bắt đầu/kết thúc `HH:MM` theo múi giờ cửa hàng. Kết thúc ≤ bắt đầu = qua nửa đêm (khung 22:00–02:00 của thứ Sáu
 * kéo tới 02:00 thứ Bảy; 00:00–00:00 = cả ngày). Dùng cho sale cuối tuần, khung giờ vàng: bảng giá, khuyến mãi, campaign —
 * luôn kết hợp với khoảng bắt đầu/kết thúc của đối tượng đó.
 *
 * @phpstan-type Window array{days: list<int>, from: string, to: string}
 */
final readonly class WeeklySchedule
{
    public const MAX_WINDOWS = 3;

    /**
     * @param  list<Window>  $windows
     */
    private function __construct(public array $windows) {}

    /**
     * Dữ liệu lưu (JSON) / gửi từ form → lịch; null hoặc rỗng = không lặp (chạy suốt khoảng thời gian).
     *
     * @throws \InvalidArgumentException dữ liệu sai ({@see self::isValid()})
     */
    public static function fromArray(mixed $data): ?self
    {
        if ($data === null || $data === []) {
            return null;
        }
        if (! self::isValid($data)) {
            throw new \InvalidArgumentException('Lịch lặp không hợp lệ.');
        }
        /** @var list<array{days: list<int|string>, from: string, to: string}> $data */
        $windows = array_map(fn (array $window): array => [
            'days' => array_values(array_unique(array_map('intval', $window['days']))),
            'from' => $window['from'],
            'to' => $window['to'],
        ], $data);

        return new self($windows);
    }

    public static function isValid(mixed $data): bool
    {
        if (! is_array($data) || ! array_is_list($data) || count($data) > self::MAX_WINDOWS) {
            return false;
        }
        foreach ($data as $window) {
            if (! is_array($window) || ! isset($window['days'], $window['from'], $window['to']) || ! is_array($window['days']) || $window['days'] === []) {
                return false;
            }
            foreach ($window['days'] as $day) {
                if (! is_numeric($day) || (int) $day < 1 || (int) $day > 7) {
                    return false;
                }
            }
            if (! self::isTime($window['from']) || ! self::isTime($window['to'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<Window>
     */
    public function toArray(): array
    {
        return $this->windows;
    }

    public function isOpenAt(int $timestamp, DateTimeZone $zone): bool
    {
        foreach ($this->intervals($timestamp, $zone, -1, 0) as [$start, $end]) {
            if ($start <= $timestamp && $timestamp < $end) {
                return true;
            }
        }

        return false;
    }

    /** Mốc mở/đóng sớm nhất sau `$timestamp` (trong 8 ngày tới) — cache trang hết hạn đúng lúc giá đổi. */
    public function nextBoundaryAfter(int $timestamp, DateTimeZone $zone): ?int
    {
        $next = null;
        foreach ($this->intervals($timestamp, $zone, -1, 7) as [$start, $end]) {
            foreach ([$start, $end] as $boundary) {
                if ($boundary > $timestamp && ($next === null || $boundary < $next)) {
                    $next = $boundary;
                }
            }
        }

        return $next;
    }

    /**
     * Các khoảng [bắt đầu, kết thúc) của mọi khung, theo ngày từ `$fromDay` tới `$toDay` (lệch so với ngày của `$timestamp`).
     *
     * @return list<array{0: int, 1: int}>
     */
    private function intervals(int $timestamp, DateTimeZone $zone, int $fromDay, int $toDay): array
    {
        $today = (new DateTimeImmutable('@'.$timestamp))->setTimezone($zone)->setTime(0, 0);
        $intervals = [];
        for ($offset = $fromDay; $offset <= $toDay; $offset++) {
            $day = $today->modify(($offset >= 0 ? '+' : '').$offset.' day');
            $weekday = (int) $day->format('N');
            foreach ($this->windows as $window) {
                if (! in_array($weekday, $window['days'], true)) {
                    continue;
                }
                [$fromHour, $fromMinute] = array_map('intval', explode(':', $window['from']));
                [$toHour, $toMinute] = array_map('intval', explode(':', $window['to']));
                $start = $day->setTime($fromHour, $fromMinute);
                $end = $day->setTime($toHour, $toMinute);
                if ($end <= $start) {
                    $end = $end->modify('+1 day');
                }
                $intervals[] = [$start->getTimestamp(), $end->getTimestamp()];
            }
        }

        return $intervals;
    }

    private static function isTime(mixed $value): bool
    {
        return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }
}
