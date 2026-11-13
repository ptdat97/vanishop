<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->actingAs(T::staff(), 'staff');
});

function materialPayload(array $overrides = []): array
{
    return array_replace([
        'code' => 'material', 'kind' => 'spec', 'input_type' => 'select', 'is_filterable' => true, 'position' => 1,
        'translations' => ['vi' => ['name' => 'Chất liệu'], 'en' => ['name' => 'Material']],
        'values' => [
            ['code' => 'silk', 'translations' => ['vi' => ['label' => 'Lụa']]],
            ['code' => 'cotton', 'translations' => ['vi' => ['label' => 'Cotton']]],
        ],
    ], $overrides);
}

it('tạo thuộc tính dạng chọn kèm giá trị', function () {
    $this->post('/admin/catalog/attributes', materialPayload())->assertSessionHasNoErrors();

    T::seed(function () {
        $attribute = Attribute::query()->where('code', 'material')->sole();
        expect($attribute->values()->pluck('code')->all())->toBe(['silk', 'cotton'])
            ->and($attribute->translate('name', 'en'))->toBe('Material');
    });
});

it('thuộc tính dạng chọn bắt buộc có giá trị', function () {
    $this->post('/admin/catalog/attributes', materialPayload(['values' => []]))->assertSessionHasErrors('values');
});

it('sửa giá trị giữ nguyên id của giá trị còn tồn tại', function () {
    $this->post('/admin/catalog/attributes', materialPayload());
    [$attribute, $silkId] = T::seed(fn () => [$a = Attribute::query()->sole(), $a->values()->where('code', 'silk')->value('id')]);

    $this->put("/admin/catalog/attributes/{$attribute->id}", materialPayload([
        'lock_version' => 0,
        'values' => [
            ['code' => 'linen', 'translations' => ['vi' => ['label' => 'Linen']]],
            ['code' => 'silk', 'translations' => ['vi' => ['label' => 'Lụa tơ tằm']]],
        ],
    ]))->assertSessionHasNoErrors();

    T::seed(function () use ($attribute, $silkId) {
        $values = $attribute->values()->get();
        expect($values->pluck('code')->all())->toBe(['linen', 'silk'])
            ->and($values->firstWhere('code', 'silk')->id)->toBe($silkId);
    });
});

it('mã thuộc tính duy nhất trong brand', function () {
    $this->post('/admin/catalog/attributes', materialPayload());
    $this->post('/admin/catalog/attributes', materialPayload())->assertSessionHasErrors('code');
});

it('tuỳ chọn biến thể (ADR-045): tạo loại (vd. Chất liệu, dropdown, lọc được) và giá trị; loại Màu có sẵn nhận hex + nhóm màu; mã giá trị duy nhất theo nhóm', function () {
    $this->post('/admin/catalog/option-types', ['code' => 'material', 'display_type' => 'dropdown', 'is_filterable' => true, 'position' => 5, 'translations' => ['vi' => ['name' => 'Chất liệu']]])
        ->assertSessionHasNoErrors();
    $material = T::seed(fn () => OptionType::query()->where('code', 'material')->sole());
    expect($material->display_type->value)->toBe('dropdown')->and($material->is_filterable)->toBeTrue();
    $this->post('/admin/catalog/option-types', ['code' => 'Có Dấu', 'display_type' => 'button', 'position' => 0, 'translations' => ['vi' => ['name' => 'X']]])->assertSessionHasErrors('code');

    $this->post("/admin/catalog/option-types/{$material->id}/values", ['code' => 'COTTON', 'position' => 1, 'translations' => ['vi' => ['name' => 'Cotton']]])->assertSessionHasNoErrors();
    $this->post("/admin/catalog/option-types/{$material->id}/values", ['code' => 'COTTON', 'position' => 2])->assertSessionHasErrors('code');

    $color = T::seed(fn () => OptionType::query()->where('code', OptionType::COLOR)->sole());
    $this->post("/admin/catalog/option-types/{$color->id}/values", ['code' => 'IVR', 'hex' => '#fffff0', 'family' => 'white', 'position' => 1, 'translations' => ['vi' => ['name' => 'Trắng ngà']]])->assertSessionHasNoErrors();
    $this->post("/admin/catalog/option-types/{$color->id}/values", ['code' => 'X', 'family' => 'rainbow', 'position' => 0])->assertSessionHasErrors('family');
    $ivory = T::seed(fn () => OptionValue::query()->where('code', 'IVR')->sole());
    expect($ivory->hex)->toBe('#FFFFF0');

    // Size: cùng mã ở hai hệ size khác nhau là hai giá trị.
    $size = T::seed(fn () => OptionType::query()->where('code', OptionType::SIZE)->sole());
    $this->post("/admin/catalog/option-types/{$size->id}/values", ['code' => 'M', 'group' => 'alpha', 'position' => 30])->assertSessionHasNoErrors();
    $this->post("/admin/catalog/option-types/{$size->id}/values", ['code' => 'M', 'group' => 'alpha', 'position' => 31])->assertSessionHasErrors('code');
    $this->post("/admin/catalog/option-types/{$size->id}/values", ['code' => 'M', 'group' => 'eu', 'position' => 1])->assertSessionHasNoErrors();

    $this->get('/admin/catalog/option-types')->assertInertia(fn (Assert $page) => $page->component('Catalog::Taxonomy/OptionTypes')->where('types.0.code', 'color'));
    $this->delete("/admin/catalog/option-types/{$color->id}/values/{$ivory->id}")->assertSessionHasNoErrors();
    $this->delete("/admin/catalog/option-types/{$material->id}")->assertSessionHasNoErrors();
});

it('không xoá loại/giá trị tuỳ chọn đang được sản phẩm dùng', function () {
    $style = T::product();
    [$variant] = PricingTestHelpers::variants($style, ['S']);
    $color = T::seed(fn () => $variant->valueOf(OptionType::COLOR) ?? $variant->load('optionValues.type')->valueOf(OptionType::COLOR));

    $this->delete("/admin/catalog/option-types/{$color->option_type_id}/values/{$color->id}")->assertSessionHasErrors('option_value');
    $this->delete("/admin/catalog/option-types/{$color->option_type_id}")->assertSessionHasErrors('option_type');
});

it('tạo nhanh từ form sản phẩm: thuộc tính (quick) quay lại form; màu mới chỉ có mã hex tự nhận nhóm màu để lọc', function () {
    $this->from('/admin/catalog/products/create')->post('/admin/catalog/attributes', [
        'quick' => 1, 'code' => 'chat_lieu', 'kind' => 'spec', 'input_type' => 'select', 'is_filterable' => true, 'position' => 0,
        'translations' => ['vi' => ['name' => 'Chất liệu']], 'values' => [['code' => 'cotton', 'translations' => ['vi' => ['label' => 'Cotton']]]],
    ])->assertRedirect('/admin/catalog/products/create')->assertSessionHasNoErrors();

    $color = T::seed(fn () => OptionType::query()->where('code', OptionType::COLOR)->sole());
    $this->post("/admin/catalog/option-types/{$color->id}/values", [
        'code' => 'XANH-NAVY', 'position' => 9, 'hex' => '#000080', 'family' => null, 'group' => null, 'translations' => ['vi' => ['name' => 'Xanh navy']],
    ])->assertSessionHasNoErrors();
    expect(T::seed(fn () => OptionValue::query()->where('code', 'XANH-NAVY')->sole()->family))->toBe('blue');
});
