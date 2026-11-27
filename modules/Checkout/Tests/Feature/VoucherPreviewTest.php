<?php

use Modules\Cart\Contracts\Carts;
use Modules\Checkout\Contracts\VoucherPreview;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/CheckoutTestHelpers.php';

beforeEach(function () {
    ['s' => $this->s] = C::store();
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $this->carts = app(Carts::class);
    $this->cart = $this->carts->create('VND')->key;
    $this->carts->addLine($this->cart, $this->s->id, 1); // 300.000đ
});

it('tính thử từng mã một mình trên giỏ, kèm khuyến mãi tự động; không đổi mã đang gắn với giỏ', function () {
    C::promotion(['name' => 'Tự động 10%', 'requires_voucher' => false, 'priority' => 10]);
    C::promotion(['name' => 'Giảm 50k', 'action_type' => 'amount_off', 'action_config' => ['amount' => 50_000]], ['GIAM50' => null]);
    C::promotion(['name' => 'Độc quyền 20%', 'stacking' => 'exclusive', 'priority' => 20, 'action_config' => ['basis_points' => 2000]], ['DOCQUYEN' => null]);
    $this->carts->setVoucherCodes($this->cart, ['GIAM50']);

    $results = app(VoucherPreview::class)->forCart($this->cart, ['giam50', 'DOCQUYEN', 'KHONGCO', 'GIAM50', ' ']);

    expect(array_map(fn ($result) => [$result->code, $result->discount->amount, $result->totalDiscount->amount, $result->rejection?->reason, $result->applicable()], $results))->toBe([
        ['GIAM50', 50_000, 30_000 + 50_000, null, true],
        // Mã độc quyền ưu tiên cao loại khuyến mãi tự động.
        ['DOCQUYEN', 60_000, 60_000, null, true],
        ['KHONGCO', 0, 30_000, 'not_found', false],
    ])->and($this->carts->view($this->cart)->voucherCodes)->toBe(['GIAM50']);
});
