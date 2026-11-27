<?php

use Modules\Shared\Domain\Schedule\WeeklySchedule;

beforeEach(function () {
    $this->zone = new DateTimeZone('Asia/Ho_Chi_Minh');
    $this->at = fn (string $local): int => (new DateTimeImmutable($local, $this->zone))->getTimestamp();
});

it('cuối tuần cả ngày và khung giờ vàng hằng ngày: mở/đóng đúng theo múi giờ cửa hàng', function () {
    $weekend = WeeklySchedule::fromArray([['days' => [6, 7], 'from' => '00:00', 'to' => '00:00']]);
    $golden = WeeklySchedule::fromArray([['days' => [1, 2, 3, 4, 5, 6, 7], 'from' => '12:00', 'to' => '14:00']]);

    // 2026-11-28 là thứ Bảy.
    expect($weekend->isOpenAt(($this->at)('2026-11-28 00:00'), $this->zone))->toBeTrue()
        ->and($weekend->isOpenAt(($this->at)('2026-11-29 23:59'), $this->zone))->toBeTrue()
        ->and($weekend->isOpenAt(($this->at)('2026-11-30 00:00'), $this->zone))->toBeFalse()
        ->and($weekend->isOpenAt(($this->at)('2026-11-27 23:59'), $this->zone))->toBeFalse()
        ->and($golden->isOpenAt(($this->at)('2026-12-01 12:00'), $this->zone))->toBeTrue()
        ->and($golden->isOpenAt(($this->at)('2026-12-01 14:00'), $this->zone))->toBeFalse();
});

it('khung qua nửa đêm thuộc ngày bắt đầu; mốc đổi kế tiếp là lúc mở hoặc đóng gần nhất', function () {
    $friday = WeeklySchedule::fromArray([['days' => [5], 'from' => '22:00', 'to' => '02:00']]);

    expect($friday->isOpenAt(($this->at)('2026-11-28 01:30'), $this->zone))->toBeTrue() // thứ Bảy 01:30, khung của thứ Sáu
        ->and($friday->isOpenAt(($this->at)('2026-11-29 01:30'), $this->zone))->toBeFalse()
        ->and($friday->nextBoundaryAfter(($this->at)('2026-11-28 01:30'), $this->zone))->toBe(($this->at)('2026-11-28 02:00'))
        ->and($friday->nextBoundaryAfter(($this->at)('2026-11-28 02:00'), $this->zone))->toBe(($this->at)('2026-12-04 22:00'));
});

it('dữ liệu sai bị từ chối; rỗng là không lặp', function () {
    expect(WeeklySchedule::fromArray(null))->toBeNull()
        ->and(WeeklySchedule::fromArray([]))->toBeNull()
        ->and(WeeklySchedule::isValid([['days' => [8], 'from' => '10:00', 'to' => '12:00']]))->toBeFalse()
        ->and(WeeklySchedule::isValid([['days' => [], 'from' => '10:00', 'to' => '12:00']]))->toBeFalse()
        ->and(WeeklySchedule::isValid([['days' => [1], 'from' => '24:00', 'to' => '12:00']]))->toBeFalse()
        ->and(WeeklySchedule::isValid(array_fill(0, 4, ['days' => [1], 'from' => '10:00', 'to' => '12:00'])))->toBeFalse()
        ->and(WeeklySchedule::fromArray([['days' => ['6', 6, 7], 'from' => '09:00', 'to' => '21:00']])->toArray())->toBe([['days' => [6, 7], 'from' => '09:00', 'to' => '21:00']]);
});
