<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Bộ chọn biến thể trên trang sản phẩm: swatch màu (radio `color`) + một nhóm size cho mỗi màu, chỉ nhóm của màu đang chọn
| hiện ra (CSS :has, không cần JS). Giá từng biến thể sẵn để JS thay khi chọn size. Server chặn biến thể khác màu đã chọn.
*/

beforeEach(function () {
    ['brand' => $brand, 's' => $this->pinkS, 'm' => $this->pinkM, 'location' => $this->location] = C::store();
    $style = $this->pinkS->style;
    // Màu thứ hai hết hàng: không được chọn sẵn, size bị khoá.
    [$this->navyS] = P::variants($style, ['S']);
    I::stock($this->location, $this->navyS->id, 0);
    P::priceList(['code' => 'navy'], [$this->navyS->id => [300_000]]); // có giá — bị khoá vì hết hàng
    $this->slug = T::seed(fn () => $style->slugFor('vi'));
    $this->pink = T::seed(fn () => $this->pinkS->valueOf('color')?->code);
    $this->navy = T::seed(fn () => $this->navyS->valueOf('color')?->code);
});

it('swatch cho mỗi màu, màu còn hàng được chọn sẵn; mỗi màu một nhóm size; size hết hàng bị khoá; giá từng biến thể sẵn cho JS', function () {
    $html = $this->get("/san-pham/{$this->slug}")->assertOk()->getContent();

    expect($html)->toContain('name="color" value="'.$this->pink.'"')
        ->and($html)->toContain('name="color" value="'.$this->navy.'"')
        ->and($html)->toMatch('/name="color" value="'.preg_quote($this->pink, '/').'"[^>]*checked/')
        ->and($html)->not->toMatch('/name="color" value="'.preg_quote($this->navy, '/').'"[^>]*checked/')
        ->and($html)->toContain('data-sizes-for="'.$this->pink.'"')
        ->and($html)->toContain('data-sizes-for="'.$this->navy.'"')
        ->and($html)->toMatch('/value="'.$this->navyS->id.'"[^>]*disabled/')
        ->and($html)->toContain('data-price-html=')
        ->and($html)->toContain('300.000');
});

it('thêm giỏ từ trang sản phẩm: biến thể phải thuộc màu đang chọn (không JS đổi màu sau khi chọn size); đúng màu thì vào giỏ', function () {
    $this->post('/gio-hang', ['variant_id' => $this->pinkS->id, 'color' => $this->navy])->assertSessionHasErrors('variant_id');
    $this->post('/gio-hang', ['variant_id' => $this->pinkS->id, 'color' => $this->pink])->assertSessionHasNoErrors();
    // Chọn màu mà chưa chọn size: báo cụ thể "chọn size".
    $this->post('/gio-hang', ['color' => $this->pink])->assertSessionHasErrors(['variant_id' => __('storefront::messages.choose_size')]);

    // API không gửi màu → không đổi hành vi.
    $cart = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $this->postJson("/api/storefront/v1/carts/{$cart->json('data.id')}/lines", ['variant_id' => $this->pinkM->id], ['X-Vani-Cart-Token' => $cart->json('meta.token')])->assertOk();
});

it('link ?sku=<mã SKU>: chọn sẵn màu và size của SKU đó; SKU hết hàng/lạ → giữ mặc định', function () {
    I::stock($this->location, $this->navyS->id, 3); // navy còn hàng
    $html = $this->get("/san-pham/{$this->slug}?sku=".strtolower($this->navyS->sku))->assertOk()->getContent();
    expect($html)->toMatch('/name="color" value="'.preg_quote($this->navy, '/').'"[^>]*checked/')
        ->and($html)->toMatch('/value="'.$this->navyS->id.'"[^>]*checked/');

    $html = $this->get("/san-pham/{$this->slug}?sku=KHONG-CO")->assertOk()->getContent();
    expect($html)->toMatch('/name="color" value="'.preg_quote($this->pink, '/').'"[^>]*checked/');
});
