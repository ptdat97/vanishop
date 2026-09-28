<?php

use Modules\Catalog\Domain\PublishWindow;
use Modules\Catalog\Domain\StyleStatus;

it('hiển thị khi active và trong khung giờ', function () {
    $window = new PublishWindow(new DateTimeImmutable('2026-10-01 00:00'), new DateTimeImmutable('2026-11-01 00:00'));

    expect(PublishWindow::isVisible(StyleStatus::Active, $window, new DateTimeImmutable('2026-10-15')))->toBeTrue()
        ->and(PublishWindow::isVisible(StyleStatus::Active, $window, new DateTimeImmutable('2026-09-30 23:59')))->toBeFalse()
        ->and(PublishWindow::isVisible(StyleStatus::Active, $window, new DateTimeImmutable('2026-11-01 00:00')))->toBeFalse()
        ->and(PublishWindow::isVisible(StyleStatus::Draft, $window, new DateTimeImmutable('2026-10-15')))->toBeFalse();
});

it('không giới hạn khi để trống', function () {
    expect((new PublishWindow)->contains(new DateTimeImmutable('2099-01-01')))->toBeTrue();
});

it('từ chối khung giờ ngược', function () {
    new PublishWindow(new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-10-01'));
})->throws(InvalidArgumentException::class);
