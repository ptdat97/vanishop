<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\SkuPattern;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Events\VariantCreated;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
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
     * @param  list<array{option_type_id: int, value_ids: list<int>}>  $axes
     *
     * @throws ValidationException
     */
    public function syncAxes(Style $style, array $axes): void
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

        $current = $style->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $hasVariants = $style->variants()->exists();
        if ($hasVariants && (array_diff($current, $typeIds) !== [] || array_diff($typeIds, $current) !== [])) {
            throw ValidationException::withMessages(['axes' => __('catalog::messages.axes_locked')]);
        }

        DB::transaction(function () use ($style, $axes, $typeIds, $current): void {
            $style->options()->whereNotIn('option_type_id', $typeIds)->delete();
            foreach ($typeIds as $position => $typeId) {
                $style->options()->updateOrCreate(['option_type_id' => $typeId], ['position' => $position]);
            }

            $wanted = array_merge([], ...array_column($axes, 'value_ids'));
            $used = DB::table('variant_option_values')->join('variants', 'variants.id', '=', 'variant_option_values.variant_id')
                ->where('variants.style_id', $style->id)->distinct()->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all();
            if (($missing = array_diff($used, $wanted)) !== []) {
                throw ValidationException::withMessages(['axes' => __('catalog::messages.option_value_in_use', ['count' => count($missing)])]);
            }
            $style->optionValues()->whereNotIn('option_value_id', $wanted)->delete();
            foreach ($axes as $axis) {
                foreach ($axis['value_ids'] as $position => $valueId) {
                    $style->optionValues()->updateOrCreate(['option_value_id' => $valueId], ['position' => $position]);
                }
            }

            if ($current !== $typeIds) {
                $this->rekey($style);
            }
        });
        $style->unsetRelation('options')->unsetRelation('optionValues');
    }

    /**
     * Tạo SKU cho một tổ hợp (một giá trị cho mỗi trục, theo thứ tự trục).
     *
     * @param  list<int>  $valueIds
     * @param  array{sku?: string|null, barcode?: string|null, status?: string, weight_gram?: int|null}  $data
     */
    public function createVariant(Style $style, array $valueIds, array $data = []): Variant
    {
        $typeIds = $style->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $values = OptionValue::query()->whereIn('id', $valueIds)->get()->keyBy('id');
        $variant = Variant::query()->create([
            'style_id' => $style->id, 'option_key' => Variant::keyFor($valueIds),
            'sku' => $data['sku'] ?? SkuPattern::make($style->style_code, ...array_map(fn (int $id): string => (string) $values->get($id)?->code, $valueIds)),
            'barcode' => $data['barcode'] ?? null, 'status' => $data['status'] ?? VariantStatus::Active->value, 'weight_gram' => $data['weight_gram'] ?? null,
        ]);
        foreach ($valueIds as $index => $valueId) {
            DB::table('variant_option_values')->insert(['variant_id' => $variant->id, 'option_type_id' => $typeIds[$index], 'option_value_id' => $valueId]);
        }
        event(new VariantCreated($variant->id, $style->id, $variant->sku));

        return $variant;
    }

    /** Tính lại khoá tổ hợp theo thứ tự trục hiện tại (sau khi đổi thứ tự trục). */
    private function rekey(Style $style): void
    {
        $order = $style->options()->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all();
        $rows = DB::table('variant_option_values')->join('variants', 'variants.id', '=', 'variant_option_values.variant_id')
            ->where('variants.style_id', $style->id)->get(['variant_option_values.variant_id', 'option_type_id', 'option_value_id'])->groupBy('variant_id');
        foreach ($rows as $variantId => $values) {
            $byType = $values->pluck('option_value_id', 'option_type_id');
            DB::table('variants')->where('id', $variantId)->update(['option_key' => Variant::keyFor(array_map(fn (int $typeId): int => (int) $byType[$typeId], $order))]);
        }
    }
}
