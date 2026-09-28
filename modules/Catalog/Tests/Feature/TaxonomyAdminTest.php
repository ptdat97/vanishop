<?php

use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Catalog\Persistence\Models\Color;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->actingAs(T::staffFor($this->brand), 'staff');
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
    $this->post('/admin/catalog/lumiere/attributes', materialPayload())->assertSessionHasNoErrors();

    T::seed(function () {
        $attribute = Attribute::query()->where('code', 'material')->sole();
        expect($attribute->values()->pluck('code')->all())->toBe(['silk', 'cotton'])
            ->and($attribute->translate('name', 'en'))->toBe('Material');
    });
});

it('thuộc tính dạng chọn bắt buộc có giá trị', function () {
    $this->post('/admin/catalog/lumiere/attributes', materialPayload(['values' => []]))->assertSessionHasErrors('values');
});

it('sửa giá trị giữ nguyên id của giá trị còn tồn tại', function () {
    $this->post('/admin/catalog/lumiere/attributes', materialPayload());
    [$attribute, $silkId] = T::seed(fn () => [$a = Attribute::query()->sole(), $a->values()->where('code', 'silk')->value('id')]);

    $this->put("/admin/catalog/lumiere/attributes/{$attribute->id}", materialPayload([
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
    $this->post('/admin/catalog/lumiere/attributes', materialPayload());
    $this->post('/admin/catalog/lumiere/attributes', materialPayload())->assertSessionHasErrors('code');
});

it('quản lý màu với nhóm màu chuẩn', function () {
    $this->post('/admin/catalog/lumiere/colors', [
        'code' => 'IVR', 'color_family' => 'white', 'hex' => '#fffff0', 'position' => 1,
        'translations' => ['vi' => ['name' => 'Trắng ngà']],
    ])->assertSessionHasNoErrors();

    $color = T::seed(fn () => Color::query()->sole());
    expect($color->hex)->toBe('#FFFFF0');

    $this->post('/admin/catalog/lumiere/colors', ['code' => 'X', 'color_family' => 'rainbow', 'position' => 0, 'translations' => ['vi' => ['name' => 'X']]])
        ->assertSessionHasErrors('color_family');
    $this->delete("/admin/catalog/lumiere/colors/{$color->id}")->assertSessionHasNoErrors();
    expect(T::seed(fn () => Color::query()->count()))->toBe(0);
});

it('size duy nhất theo hệ size', function () {
    $this->post('/admin/catalog/lumiere/sizes', ['size_system' => 'alpha', 'code' => 'M', 'sort_order' => 30])->assertSessionHasNoErrors();
    $this->post('/admin/catalog/lumiere/sizes', ['size_system' => 'alpha', 'code' => 'M', 'sort_order' => 31])->assertSessionHasErrors('code');
    $this->post('/admin/catalog/lumiere/sizes', ['size_system' => 'eu', 'code' => 'M', 'sort_order' => 1])->assertSessionHasNoErrors();

    expect(T::seed(fn () => Size::query()->count()))->toBe(2);
});
