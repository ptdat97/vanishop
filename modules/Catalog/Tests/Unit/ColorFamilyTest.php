<?php

use Modules\Catalog\Domain\ColorFamily;

it('nhận nhóm màu từ mã hex (tạo nhanh màu mới không cần chọn nhóm)', function (string $hex, ?ColorFamily $family) {
    expect(ColorFamily::nearest($hex))->toBe($family);
})->with([
    ['#FFFFF0', ColorFamily::White],
    ['#111111', ColorFamily::Black],
    ['#808080', ColorFamily::Grey],
    ['#F5F5DC', ColorFamily::Beige],
    ['#8B4513', ColorFamily::Brown],
    ['#C0392B', ColorFamily::Red],
    ['#FFC0CB', ColorFamily::Pink],
    ['#FF7F50', ColorFamily::Orange],
    ['#FFD700', ColorFamily::Yellow],
    ['#2E8B57', ColorFamily::Green],
    ['#000080', ColorFamily::Blue],
    ['#800080', ColorFamily::Purple],
    ['xanh', null],
]);
