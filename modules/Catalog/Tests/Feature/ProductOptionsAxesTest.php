<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;

require_once __DIR__.'/CatalogTestHelpers.php';

/*
| Giá trị tuỳ chọn của sản phẩm gắn với trục (`product_option_values.product_option_id`, db-refactor §4.6).
*/

beforeEach(function () {
    $this->product = T::product();
    $this->color = OptionValue::factory()->color()->create();
    $this->size = OptionValue::factory()->size()->create();
});

it('từ chối giá trị không thuộc loại của trục', function () {
    expect(fn () => app(ProductOptions::class)->syncAxes($this->product, [
        ['option_type_id' => $this->color->option_type_id, 'value_ids' => [$this->size->id]],
    ]))->toThrow(ValidationException::class);

    expect(DB::table('product_option_values')->count())->toBe(0);
});

it('bỏ một trục thì giá trị của trục đó đi theo, trục còn lại giữ nguyên', function () {
    $options = app(ProductOptions::class);
    $options->syncAxes($this->product, [
        ['option_type_id' => $this->color->option_type_id, 'value_ids' => [$this->color->id]],
        ['option_type_id' => $this->size->option_type_id, 'value_ids' => [$this->size->id]],
    ]);

    $options->syncAxes($this->product, [['option_type_id' => $this->size->option_type_id, 'value_ids' => [$this->size->id]]]);

    expect($this->product->optionValues()->pluck('option_value_id')->all())->toBe([$this->size->id])
        ->and(DB::table('product_option_values')->count())->toBe(1);
});
