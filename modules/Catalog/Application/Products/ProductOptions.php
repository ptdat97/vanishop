<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\SkuPattern;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Events\VariantCreated;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Catalog\Persistence\Models\Variant;

/**
 * Trục biến thể của sản phẩm và tạo SKU theo tổ hợp (ADR-045). Dùng chung cho ma trận Admin và trình nhập sản phẩm.
 */
final class ProductOptions
{
    public const MAX_AXES = 3;

    /**
     * Ghi trục (1–3 loại tuỳ chọn theo thứ tự) và giá trị sản phẩm dùng (theo thứ tự). Đổi tập loại khi đã có SKU → lỗi
     * (phải tắt hết SKU trước); đổi thứ tự trục → tính lại khoá tổ hợp của mọi SKU. Bỏ giá trị đang có SKU → lỗi.
     *
     * `$fromSingle`: sản phẩm đơn (chưa có trục, một SKU không tuỳ chọn) đang chuyển sang có biến thể — cho đặt trục lần đầu dù
     * đã có SKU; SKU đó được gắn vào một tổ hợp ngay sau (`adoptSingleVariant`).
     *
     * @param  list<array{option_type_id: int, value_ids: list<int>}>  $axes
     *
     * @throws ValidationException
     */
    public function syncAxes(Product $product, array $axes, bool $fromSingle = false): void
    {
        if ($axes === [] || count($axes) > self::MAX_AXES) {
            throw ValidationException::withMessages(['axes' => __('catalog::messages.axes_count', ['max' => self::MAX_AXES])]);
        }
        $typeIds = array_map(fn (array $axis): int => (int) $axis['option_type_id'], $axes);
        if (count(array_unique($typeIds)) !== count($typeIds)) {
            throw ValidationException::withMessages(['axes' => __('catalog::messages.axes_duplicate')]);
        }
        $values = OptionValue::query()->whereIn('id', array_merge([], ...array_column($axes, 'value_ids')))->get()->keyBy('id');
        foreach ($axes as $index => $axis) {
            if ($axis['value_ids'] === []) {
                throw ValidationException::withMessages(["axes.{$index}.value_ids" => __('catalog::messages.axis_needs_values')]);
            }
            foreach ($axis['value_ids'] as $valueId) {
                if ($values->get($valueId)?->option_type_id !== (int) $axis['option_type_id']) {
                    throw ValidationException::withMessages(["axes.{$index}.value_ids" => __('catalog::messages.option_value_invalid')]);
                }
            }
        }

        $current = $product->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $hasVariants = $product->variants()->exists();
        if ($hasVariants && ! ($fromSingle && $current === []) && (array_diff($current, $typeIds) !== [] || array_diff($typeIds, $current) !== [])) {
            throw ValidationException::withMessages(['axes' => __('catalog::messages.axes_locked')]);
        }

        DB::transaction(function () use ($product, $axes, $typeIds, $current): void {
            $product->options()->whereNotIn('option_type_id', $typeIds)->delete();
            foreach ($typeIds as $position => $typeId) {
                $product->options()->updateOrCreate(['option_type_id' => $typeId], ['position' => $position]);
            }

            $wanted = array_merge([], ...array_column($axes, 'value_ids'));
            $used = DB::table('variant_option_values')->join('variants', 'variants.id', '=', 'variant_option_values.variant_id')
                ->where('variants.product_id', $product->id)->distinct()->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all();
            if (($missing = array_diff($used, $wanted)) !== []) {
                throw ValidationException::withMessages(['axes' => __('catalog::messages.option_value_in_use', ['count' => count($missing)])]);
            }
            $product->optionValues()->whereNotIn('option_value_id', $wanted)->delete();
            foreach ($axes as $axis) {
                foreach ($axis['value_ids'] as $position => $valueId) {
                    $product->optionValues()->updateOrCreate(['option_value_id' => $valueId], ['position' => $position]);
                }
            }

            if ($current !== $typeIds) {
                $this->rekey($product);
            }
        });
        $product->unsetRelation('options')->unsetRelation('optionValues');
    }

    /**
     * Tạo SKU cho một tổ hợp (một giá trị cho mỗi trục, theo thứ tự trục).
     *
     * @param  list<int>  $valueIds
     * @param  array{sku?: string|null, barcode?: string|null, status?: string, weight_gram?: int|null}  $data
     */
    public function createVariant(Product $product, array $valueIds, array $data = []): Variant
    {
        $typeIds = $product->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $values = OptionValue::query()->whereIn('id', $valueIds)->get()->keyBy('id');
        $variant = Variant::query()->create([
            'product_id' => $product->id, 'option_key' => Variant::keyFor($valueIds),
            'sku' => $data['sku'] ?? SkuPattern::make($product->product_code, ...array_map(fn (int $id): string => (string) $values->get($id)?->code, $valueIds)),
            'barcode' => $data['barcode'] ?? null, 'status' => $data['status'] ?? VariantStatus::Active->value, 'weight_gram' => $data['weight_gram'] ?? null,
        ]);
        foreach ($valueIds as $index => $valueId) {
            DB::table('variant_option_values')->insert(['variant_id' => $variant->id, 'option_type_id' => $typeIds[$index], 'option_value_id' => $valueId]);
        }
        event(new VariantCreated($variant->id, $product->id, $variant->sku));

        return $variant;
    }

    /**
     * Gắn SKU của sản phẩm đơn vào một tổ hợp (một giá trị cho mỗi trục, theo thứ tự trục) — giữ nguyên SKU (id, giá, tồn, ảnh,
     * lịch sử đơn), chỉ thêm giá trị tuỳ chọn. Gọi sau `syncAxes(..., fromSingle: true)`.
     *
     * @param  list<int>  $valueIds
     */
    public function adoptSingleVariant(Product $product, Variant $variant, array $valueIds): void
    {
        $typeIds = $product->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        foreach ($valueIds as $index => $valueId) {
            DB::table('variant_option_values')->insert(['variant_id' => $variant->id, 'option_type_id' => $typeIds[$index], 'option_value_id' => $valueId]);
        }
        $variant->forceFill(['option_key' => Variant::keyFor($valueIds)])->save();
        $variant->unsetRelation('optionValues');
    }

    /**
     * Về sản phẩm đơn: bỏ trục và giá trị của sản phẩm, SKU giữ lại bỏ giá trị tuỳ chọn (giữ id — đơn, tồn, giá vẫn gắn với SKU;
     * dòng đơn cũ đã chụp sẵn màu/size). Gọi khi mọi SKU khác đã bị xoá.
     */
    public function collapseToSingle(Product $product, Variant $kept): void
    {
        DB::table('variant_option_values')->where('variant_id', $kept->id)->delete();
        $kept->forceFill(['option_key' => Variant::keyFor([])])->save();
        $kept->unsetRelation('optionValues');
        $product->optionValues()->delete();
        $product->options()->delete();
        $product->unsetRelation('options')->unsetRelation('optionValues');
    }

    /** Tính lại khoá tổ hợp theo thứ tự trục hiện tại (sau khi đổi thứ tự trục). */
    private function rekey(Product $product): void
    {
        $order = $product->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $rows = DB::table('variant_option_values')->join('variants', 'variants.id', '=', 'variant_option_values.variant_id')
            ->where('variants.product_id', $product->id)->get(['variant_option_values.variant_id', 'option_type_id', 'option_value_id'])->groupBy('variant_id');
        foreach ($rows as $variantId => $values) {
            $byType = $values->pluck('option_value_id', 'option_type_id');
            DB::table('variants')->where('id', $variantId)->update(['option_key' => Variant::keyFor(array_map(fn (int $typeId): int => (int) $byType[$typeId], $order))]);
        }
    }
}
