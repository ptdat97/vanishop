<?php

use Modules\Pricing\Domain\PriceCandidate;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Domain\PriceSelection;
use Modules\Shared\Domain\Money\Money;

function candidate(int $id, PriceListType $type, int $priority, int $amount, ?int $compareAt = null): PriceCandidate
{
    return new PriceCandidate($id, "L{$id}", $type, $priority, Money::vnd($amount), $compareAt === null ? null : Money::vnd($compareAt));
}

it('không có ứng viên thì không có giá', function () {
    expect(PriceSelection::choose([]))->toBeNull();
});

it('bảng giá priority cao hơn thắng, giá base làm giá gốc', function () {
    $selected = PriceSelection::choose([
        candidate(1, PriceListType::Base, 0, 590_000),
        candidate(2, PriceListType::Sale, 10, 413_000),
    ]);

    expect($selected->amount->amount)->toBe(413_000)
        ->and($selected->compareAt->amount)->toBe(590_000)
        ->and($selected->priceListCode)->toBe('L2')
        ->and($selected->discountPercent())->toBe(30);
});

it('cùng priority thì lấy giá thấp hơn', function () {
    $selected = PriceSelection::choose([
        candidate(1, PriceListType::Sale, 5, 450_000),
        candidate(2, PriceListType::Sale, 5, 420_000),
        candidate(3, PriceListType::Base, 0, 590_000),
    ]);

    expect($selected->amount->amount)->toBe(420_000)->and($selected->priceListId)->toBe(2);
});

it('dùng compare_at khai báo nếu lớn hơn giá bán', function () {
    $selected = PriceSelection::choose([candidate(1, PriceListType::Base, 0, 490_000, 690_000)]);

    expect($selected->compareAt->amount)->toBe(690_000)->and($selected->discountPercent())->toBe(28);
});

it('không hiển thị giảm giá khi giá "khuyến mãi" cao hơn giá gốc', function () {
    $selected = PriceSelection::choose([
        candidate(1, PriceListType::Base, 0, 400_000),
        candidate(2, PriceListType::Sale, 10, 450_000),
    ]);

    expect($selected->amount->amount)->toBe(450_000)
        ->and($selected->compareAt)->toBeNull()
        ->and($selected->discountPercent())->toBeNull();
});

it('bỏ qua compare_at không hợp lệ (nhỏ hơn hoặc bằng giá bán)', function () {
    expect(PriceSelection::choose([candidate(1, PriceListType::Base, 0, 500_000, 500_000)])->compareAt)->toBeNull();
});
