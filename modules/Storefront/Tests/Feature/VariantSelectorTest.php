<?php

use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Bộ chọn biến thể trên trang sản phẩm (ADR-045, 1–3 trục): trục đầu là radio `axis[<mã loại>]` (ô màu / nút / danh sách),
| mỗi giá trị một nhóm SKU theo các trục còn lại, chỉ nhóm của giá trị đang chọn hiện ra (CSS :has, không cần JS). Một trục:
| chọn thẳng SKU. Giá từng SKU sẵn để JS thay. Server chặn SKU không mang giá trị trục đầu đã chọn.
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

    expect($html)->toContain('name="axis[color]" value="'.$this->pink.'"')
        ->and($html)->toContain('name="axis[color]" value="'.$this->navy.'"')
        ->and($html)->toMatch('/name="axis\\[color\\]" value="'.preg_quote($this->pink, '/').'"[^>]*checked/')
        ->and($html)->not->toMatch('/name="axis\\[color\\]" value="'.preg_quote($this->navy, '/').'"[^>]*checked/')
        ->and($html)->toContain('<fieldset data-option-group="'.$this->pink.'"')
        ->and($html)->toContain('<fieldset data-option-group="'.$this->navy.'"')
        ->and($html)->toMatch('/value="'.$this->navyS->id.'"[^>]*disabled/')
        ->and($html)->toContain('data-price-html=')
        ->and($html)->toContain('300.000');
});

it('thêm giỏ từ trang sản phẩm: SKU phải mang giá trị trục đầu đang chọn (không JS đổi màu sau khi chọn size); đúng màu thì vào giỏ', function () {
    $this->post('/gio-hang', ['variant_id' => $this->pinkS->id, 'axis' => ['color' => $this->navy]])->assertSessionHasErrors('variant_id');
    $this->post('/gio-hang', ['variant_id' => $this->pinkS->id, 'axis' => ['color' => $this->pink]])->assertSessionHasNoErrors();
    // Chọn trục đầu mà chưa chọn SKU: báo còn thiếu tuỳ chọn; JS báo theo tên trục (data-choose-message).
    $this->post('/gio-hang', ['axis' => ['color' => $this->pink]])->assertSessionHasErrors(['variant_id' => __('storefront::messages.choose_remaining')]);
    expect($this->get("/san-pham/{$this->slug}")->getContent())->toContain('data-choose-message="'.e(__('storefront::messages.choose_option', ['name' => 'Size'])).'"');

    // API không gửi màu → không đổi hành vi.
    $cart = $this->postJson('/api/storefront/v1/carts')->assertCreated();
    $this->postJson("/api/storefront/v1/carts/{$cart->json('data.id')}/lines", ['variant_id' => $this->pinkM->id], ['X-Vani-Cart-Token' => $cart->json('meta.token')])->assertOk();
});

it('link ?sku=<mã SKU>: chọn sẵn màu và size của SKU đó; SKU hết hàng/lạ → giữ mặc định', function () {
    I::stock($this->location, $this->navyS->id, 3); // navy còn hàng
    $html = $this->get("/san-pham/{$this->slug}?sku=".strtolower($this->navyS->sku))->assertOk()->getContent();
    expect($html)->toMatch('/name="axis\\[color\\]" value="'.preg_quote($this->navy, '/').'"[^>]*checked/')
        ->and($html)->toMatch('/value="'.$this->navyS->id.'"[^>]*checked/');

    $html = $this->get("/san-pham/{$this->slug}?sku=KHONG-CO")->assertOk()->getContent();
    expect($html)->toMatch('/name="axis\\[color\\]" value="'.preg_quote($this->pink, '/').'"[^>]*checked/');
});

it('một trục (chỉ size): chọn thẳng SKU, SKU mặc định chọn sẵn, không có nhóm phụ', function () {
    $style = T::product(null, ['name' => 'Tất cổ ngắn']);
    $sizes = T::seed(fn () => [OptionValue::factory()->size()->create(['code' => 'F1']), OptionValue::factory()->size()->create(['code' => 'F2'])]);
    T::optionValues($style, ...$sizes);
    [$first, $second] = T::seed(fn () => array_map(fn (OptionValue $size) => app(ProductOptions::class)->createVariant($style, [$size->id]), $sizes));
    P::priceList(['code' => 'socks'], [$first->id => [50_000], $second->id => [50_000]]);
    I::stock($this->location, $first->id, 5);
    I::stock($this->location, $second->id, 5);

    $html = $this->get('/san-pham/'.T::seed(fn () => $style->slugFor('vi')))->assertOk()->getContent();
    expect($html)->toMatch('/name="variant_id" value="'.$first->id.'"[^>]*data-option-value="F1"[^>]*checked/')
        ->and($html)->not->toContain('<fieldset data-option-group')
        ->and($html)->not->toContain('name="axis[');
});

it('ba trục, trục đầu kiểu danh sách thả xuống: nhóm SKU nhãn theo hai trục còn lại', function () {
    $style = T::product(null, ['name' => 'Giày da']);
    [$material, $leather, $color, $size] = T::seed(function () {
        $material = OptionType::factory()->create(['code' => 'material', 'display_type' => 'dropdown']);
        $material->syncTranslations(['vi' => ['name' => 'Chất liệu']]);
        $leather = OptionValue::factory()->create(['option_type_id' => $material->id, 'code' => 'LEATHER']);
        $leather->syncTranslations(['vi' => ['name' => 'Da bò']]);

        return [$material, $leather, OptionValue::factory()->color()->create(['code' => 'BRN']), OptionValue::factory()->size()->create(['code' => '42'])];
    });
    T::optionValues($style, $leather, $color, $size);
    $variant = T::seed(fn () => app(ProductOptions::class)->createVariant($style, [$leather->id, $color->id, $size->id]));
    P::priceList(['code' => 'shoes'], [$variant->id => [900_000]]);
    I::stock($this->location, $variant->id, 2);

    $html = $this->get('/san-pham/'.T::seed(fn () => $style->slugFor('vi')))->assertOk()->getContent();
    expect($html)->toContain('<select name="axis[material]"')
        ->and($html)->toMatch('/<option value="LEATHER" data-primary data-option-value="LEATHER"[^>]*selected/')
        ->and($html)->toContain('<fieldset data-option-group="LEATHER"')
        ->and($html)->toMatch('/name="variant_id" value="'.$variant->id.'"/')
        ->and($html)->toContain('Màu '.$color->code.' / 42')
        ->and($html)->toContain(e(__('storefront::messages.choose_option', ['name' => 'Màu, Size'])));
    $this->post('/gio-hang', ['variant_id' => $variant->id, 'axis' => ['material' => 'LEATHER']])->assertSessionHasNoErrors();
});
