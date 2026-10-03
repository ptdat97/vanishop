<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Khoảng thời gian báo cáo [from, to) theo múi giờ cửa hàng. Core dựng từ lựa chọn trên Admin.
 */
final readonly class ReportPeriod
{
    public const TIMEZONE = 'Asia/Ho_Chi_Minh';

    public const PRESETS = ['today' => 'Hôm nay', '7d' => '7 ngày', '30d' => '30 ngày', 'this_month' => 'Tháng này', 'last_month' => 'Tháng trước', 'custom' => 'Tuỳ chọn'];

    public function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
        public string $preset = 'custom',
        public string $timezone = self::TIMEZONE,
    ) {
        if ($to <= $from) {
            throw new InvalidArgumentException('Ngày kết thúc phải sau ngày bắt đầu.');
        }
    }

    /**
     * `custom`: `$start`/`$end` là ngày Y-m-d (bao gồm cả ngày kết thúc).
     */
    public static function fromPreset(string $preset, ?string $start = null, ?string $end = null, ?DateTimeImmutable $now = null, string $timezone = self::TIMEZONE): self
    {
        $zone = new DateTimeZone($timezone);
        // CarbonImmutable::now() theo đồng hồ của ứng dụng (test cố định được giờ).
        $today = DateTimeImmutable::createFromInterface($now ?? CarbonImmutable::now())->setTimezone($zone)->setTime(0, 0);

        return match ($preset) {
            'today' => new self($today, $today->modify('+1 day'), $preset, $timezone),
            '7d' => new self($today->modify('-6 days'), $today->modify('+1 day'), $preset, $timezone),
            '30d' => new self($today->modify('-29 days'), $today->modify('+1 day'), $preset, $timezone),
            'this_month' => new self($today->modify('first day of this month'), $today->modify('+1 day'), $preset, $timezone),
            'last_month' => new self($today->modify('first day of last month'), $today->modify('first day of this month'), $preset, $timezone),
            'custom' => self::custom($start, $end, $zone, $timezone),
            default => throw new InvalidArgumentException("Khoảng thời gian [{$preset}] không hợp lệ."),
        };
    }

    private static function custom(?string $start, ?string $end, DateTimeZone $zone, string $timezone): self
    {
        $from = $start === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $start, $zone);
        $to = $end === null ? false : DateTimeImmutable::createFromFormat('!Y-m-d', $end, $zone);
        if ($from === false || $to === false) {
            throw new InvalidArgumentException('Ngày phải theo dạng YYYY-MM-DD.');
        }

        return new self($from, $to->modify('+1 day'), 'custom', $timezone);
    }

    /** Ngày cuối (bao gồm), Y-m-d. */
    public function lastDay(): string
    {
        return $this->to->modify('-1 second')->format('Y-m-d');
    }

    public function days(): int
    {
        return (int) $this->from->diff($this->to)->days;
    }

    /**
     * @return array{preset: string, start: string, end: string}
     */
    public function toArray(): array
    {
        return ['preset' => $this->preset, 'start' => $this->from->format('Y-m-d'), 'end' => $this->lastDay()];
    }
}
