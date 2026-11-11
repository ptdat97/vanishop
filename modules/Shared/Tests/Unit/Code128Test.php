<?php

use Modules\Shared\Support\Code128;

it('mã hoá bộ mã B: Start B, giá trị ký tự, ký tự kiểm tra theo trọng số vị trí, Stop', function () {
    // "AB": A = 33, B = 34 → (104 + 33×1 + 34×2) mod 103 = 102.
    expect(Code128::symbols('AB'))->toBe([104, 33, 34, 102, 106])
        // Mỗi ký hiệu 11 module, Stop 13 module.
        ->and(strlen((string) Code128::modules('VN000123')))->toBe(11 * (1 + 8 + 1) + 13)
        // Start B = 211214, kết thúc bằng thanh 2 module của Stop.
        ->and(Code128::modules('AB'))->toStartWith('11010010000')->toEndWith('1100011101011');
});

it('từ chối chuỗi rỗng, quá dài hoặc có ký tự ngoài ASCII in được', function (string $text) {
    expect(Code128::symbols($text))->toBeNull()->and(Code128::svg($text))->toBeNull();
})->with(['', 'Đơn 01', "VN\t01", str_repeat('9', 49)]);

it('SVG chỉ gồm các thanh, có vùng trắng hai bên và không chứa chuỗi đầu vào', function () {
    $svg = (string) Code128::svg('VN000123', 40);
    $modules = (string) Code128::modules('VN000123');

    expect($svg)->toStartWith('<svg ')->toContain('viewBox="0 0 '.(strlen($modules) + 20).' 40"')
        ->not->toContain('VN000123')
        ->and(substr_count($svg, '<rect'))->toBe(1 + preg_match_all('/1+/', $modules))
        // Thanh đầu tiên nằm sau vùng trắng 10 module.
        ->and($svg)->toContain('<g fill="#000"><rect x="10" y="0" width="2"');
});
