<?php

use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Products\ProductOptions;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Inventory/Tests/Feature/InventoryTestHelpers.php';

/*
| Ma trận biến thể theo trục tuỳ chọn (ADR-045; docs/03-domains/variant-matrix.md §3): lưu cả lưới một lần — SKU của Catalog, cột giá
| (Pricing) và tồn (Inventory) góp qua VariantFieldProvider, cột của plugin; tắt ô: chưa phát sinh → xoá, đã phát sinh →
| ngừng bán (VariantUsage).
*/

/** Cột của plugin mẫu: "Số đo ngực (cm)" theo SKU, công bố ra storefront. */
final class ChestFieldProvider implements VariantFieldProvider
{
    /** @var array<int, array<string, mixed>> */
    public static array $saved = [];

    public function code(): string
    {
        return 'demo-measure';
    }

    public function label(): string
    {
        return 'Số đo';
    }

    public function fields(): array
    {
        return [FieldDefinition::int('chest', 'Ngực (cm)')];
    }

    public function permission(): ?string
    {
        return null;
    }

    public function values(int $styleId, array $variantIds): array
    {
        return array_intersect_key(self::$saved, array_flip($variantIds));
    }

    public function save(int $styleId, array $values): array
    {
        self::$saved = array_replace(self::$saved, $values);

        return [];
    }

    public function storefront(array $variantIds): array
    {
        return array_intersect_key(self::$saved, array_flip($variantIds));
    }
}

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.manage', 'inventory.adjust']), 'staff');
    $this->style = T::product($this->brand->id, ['style_code' => 'LM24-SH012']);
    $this->base = "/admin/catalog/products/{$this->style->id}";
    $this->location = I::location(['code' => 'WH-MAIN']);
    ChestFieldProvider::$saved = [];

    T::seed(function () {
        $this->ivory = OptionValue::factory()->color()->create(['code' => 'IVR']);
        $this->black = OptionValue::factory()->color()->create(['code' => 'BLK']);
        $this->s = OptionValue::factory()->size()->create(['code' => 'S', 'position' => 20]);
        $this->m = OptionValue::factory()->size()->create(['code' => 'M', 'position' => 30]);
        $this->colorType = (int) OptionType::query()->where('code', OptionType::COLOR)->value('id');
        $this->sizeType = (int) OptionType::query()->where('code', OptionType::SIZE)->value('id');
    });
    $this->cell = fn (OptionValue $color, OptionValue $size, array $overrides = []): array => [
        'values' => [$color->id, $size->id], 'enabled' => true, 'status' => 'active',
        'sku' => "LM24-SH012-{$color->code}-{$size->code}", 'barcode' => null, 'weight_gram' => 250, 'fields' => [], ...$overrides,
    ];
    $this->save = fn (array $cells, ?array $colors = null) => $this->put("{$this->base}/variants", ['axes' => [
        ['option_type_id' => $this->colorType, 'value_ids' => $colors ?? [$this->ivory->id, $this->black->id]],
        ['option_type_id' => $this->sizeType, 'value_ids' => [$this->s->id, $this->m->id]],
    ], 'cells' => $cells]);
    $this->colorOrder = fn (): array => T::seed(fn () => $this->style->optionValues()->whereIn('option_value_id', [$this->ivory->id, $this->black->id])->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all());
});

it('màn hình sửa sản phẩm có cột góp vào (giá, tồn ở kho duy nhất) và giá trị hiện tại theo SKU', function () {
    ($this->save)([($this->cell)($this->ivory, $this->s, ['fields' => ['pricing' => ['price' => 350_000], 'inventory' => ['on_hand' => 7]]])])->assertRedirect()->assertSessionHasNoErrors();
    $variant = T::seed(fn () => Variant::query()->sole());

    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page
        ->where('variantMatrix.columns.0.code', 'pricing')
        ->where('variantMatrix.columns.0.editable', true)
        ->where('variantMatrix.columns.1.code', 'inventory')
        ->where("variantMatrix.values.{$variant->id}", ['pricing' => ['price' => 350_000, 'compare_at' => null], 'inventory' => ['on_hand' => 7]])
        ->where('variantMatrix.axes.0.option_type_id', $this->colorType)->has('variantMatrix.optionTypes'));
});

it('lưu cả lưới một lần: tạo SKU, giá niêm yết, tồn; đổi thứ tự giá trị trục; sửa SKU/trạng thái ở lần lưu sau', function () {
    ($this->save)([
        ($this->cell)($this->ivory, $this->s, ['fields' => ['pricing' => ['price' => 350_000, 'compare_at' => 490_000], 'inventory' => ['on_hand' => 5]]]),
        ($this->cell)($this->ivory, $this->m, ['fields' => ['pricing' => ['price' => 350_000]]]),
        ($this->cell)($this->black, $this->s),
    ], [$this->black->id, $this->ivory->id])->assertRedirect()->assertSessionHasNoErrors();

    $variants = T::seed(fn () => Variant::query()->get()->keyBy('sku'));
    $ivoryS = $variants['LM24-SH012-IVR-S'];
    expect($variants)->toHaveCount(3)
        ->and($ivoryS->weight_gram)->toBe(250)
        ->and(DB::table('prices')->where('variant_id', $ivoryS->id)->first())->amount->toBe(350_000)->compare_at_amount->toBe(490_000)
        ->and((int) DB::table('stock_levels')->where('variant_id', $ivoryS->id)->value('on_hand'))->toBe(5)
        ->and(($this->colorOrder)())->toBe([$this->black->id, $this->ivory->id]);

    ($this->save)([($this->cell)($this->ivory, $this->s, ['sku' => 'lm24-ivr-s', 'status' => 'inactive'])])->assertRedirect()->assertSessionHasNoErrors();
    expect($ivoryS->fresh())->sku->toBe('LM24-IVR-S')->status->value->toBe('inactive');
});

it('tắt ô: SKU chưa phát sinh → xoá (dọn giá, dòng tồn 0); đã có trong đơn/giỏ/biến động tồn → chỉ ngừng bán', function () {
    ($this->save)([
        ($this->cell)($this->ivory, $this->s, ['fields' => ['pricing' => ['price' => 300_000]]]),
        ($this->cell)($this->ivory, $this->m, ['fields' => ['pricing' => ['price' => 300_000], 'inventory' => ['on_hand' => 3]]]),
    ])->assertRedirect()->assertSessionHasNoErrors();
    $variants = T::seed(fn () => Variant::query()->get()->keyBy('sku'));

    ($this->save)([
        ($this->cell)($this->ivory, $this->s, ['enabled' => false]),
        ($this->cell)($this->ivory, $this->m, ['enabled' => false]),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Variant::query()->find($variants['LM24-SH012-IVR-S']->id))->toBeNull()
        ->and(DB::table('prices')->where('variant_id', $variants['LM24-SH012-IVR-S']->id)->exists())->toBeFalse()
        // IVR-M có biến động tồn (kiểm kê) → giữ, ngừng bán.
        ->and($variants['LM24-SH012-IVR-M']->fresh()->status->value)->toBe('inactive');

    // Giá trị không còn SKU thì gỡ khỏi trục được; giá trị còn SKU (kể cả ngừng bán) thì không.
    ($this->save)([], [$this->ivory->id])->assertRedirect()->assertSessionHasNoErrors();
    expect(($this->colorOrder)())->toBe([$this->ivory->id]);
    ($this->save)([], [$this->black->id])->assertSessionHasErrors('axes');
});

it('kiểm tra: SKU trùng trong lưới hoặc với sản phẩm khác, sai dạng, giá trị không thuộc trục → không lưu gì', function () {
    $other = T::product($this->brand->id, ['style_code' => 'OTHER']);
    T::optionValues($other, $this->ivory, $this->s);
    T::seed(fn () => app(ProductOptions::class)->createVariant($other, [$this->ivory->id, $this->s->id], ['sku' => 'TAKEN-1']));

    ($this->save)([($this->cell)($this->ivory, $this->s, ['sku' => 'TAKEN-1'])])->assertSessionHasErrors('cells.0.sku');
    ($this->save)([($this->cell)($this->ivory, $this->s, ['sku' => 'SAME']), ($this->cell)($this->black, $this->s, ['sku' => 'SAME'])])->assertSessionHasErrors('cells.1.sku');
    ($this->save)([($this->cell)($this->ivory, $this->s, ['sku' => 'có dấu'])])->assertSessionHasErrors('cells.0.sku');
    $foreign = T::seed(fn () => OptionValue::factory()->color()->create());
    ($this->save)([($this->cell)($foreign, $this->s)])->assertSessionHasErrors('cells.0.values');

    expect(T::seed(fn () => Variant::query()->where('style_id', $this->style->id)->count()))->toBe(0);
});

it('cột theo quyền: thiếu quyền giá thì giá gửi lên bị bỏ qua, cột chỉ đọc; lỗi của cột báo theo SKU, phần Catalog vẫn lưu', function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage']), 'staff');
    ($this->save)([($this->cell)($this->ivory, $this->s, ['fields' => ['pricing' => ['price' => 1]]])])->assertRedirect()->assertSessionHasNoErrors();
    $variant = T::seed(fn () => Variant::query()->sole());
    expect(DB::table('prices')->where('variant_id', $variant->id)->exists())->toBeFalse();
    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page->where('variantMatrix.columns.0.editable', false));

    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.manage']), 'staff');
    ($this->save)([($this->cell)($this->ivory, $this->s, ['weight_gram' => 300, 'fields' => ['pricing' => ['price' => 300_000, 'compare_at' => 200_000]]])])
        ->assertSessionHasErrors("variant.{$variant->id}");
    expect($variant->fresh()->weight_gram)->toBe(300);
});

it('plugin góp cột theo SKU: hiện trong màn hình, lưu qua nhà cung cấp, công bố ra Storefront API ở variants[].extensions', function () {
    app(Extensions::class)->tag([ChestFieldProvider::class], VariantFieldProvider::TAG);
    ($this->save)([($this->cell)($this->ivory, $this->s, ['fields' => ['demo-measure' => ['chest' => 88], 'pricing' => ['price' => 300_000], 'inventory' => ['on_hand' => 2]]])])->assertRedirect()->assertSessionHasNoErrors();
    $variant = T::seed(fn () => Variant::query()->sole());

    expect(ChestFieldProvider::$saved)->toBe([$variant->id => ['chest' => 88]]);
    $this->get("{$this->base}/edit")->assertInertia(fn (Assert $page) => $page->where("variantMatrix.values.{$variant->id}.demo-measure.chest", 88));

    $slug = T::seed(fn () => $this->style->slugFor('vi'));
    $this->getJson("/api/storefront/v1/products/{$slug}")->assertOk()
        ->assertJsonPath('data.variants.0.extensions.demo-measure.chest', 88);
});
