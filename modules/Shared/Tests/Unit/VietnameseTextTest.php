<?php

use Modules\Shared\Domain\Text\VietnameseText;

it('bỏ dấu, chữ thường, đ thành d', function (string $input, string $expected) {
    expect(VietnameseText::normalize($input))->toBe($expected);
})->with([
    ['Áo Sơ Mi Lụa ĐEN', 'ao so mi lua den'],
    ['Đầm  dạ hội — màu Đỏ!', 'dam da hoi mau do'],
    ['Quần jeans ống suông 2024', 'quan jeans ong suong 2024'],
    ['  ', ''],
]);

it('tách từ khoá không trùng', function () {
    expect(VietnameseText::tokens('Áo áo SƠ MI'))->toBe(['ao', 'so', 'mi'])
        ->and(VietnameseText::tokens(''))->toBe([]);
});
