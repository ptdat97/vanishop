<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Cột "Giá" của ma trận biến thể (variant-matrix §3.3): giá niêm yết và giá so sánh của bảng giá `base` theo SKU. SKU bị xoá
 * khỏi ma trận (chưa phát sinh) → gỡ giá của nó ở mọi bảng giá (`release`); lịch sử giá giữ lại.
 */
final class VariantPriceFields implements VariantFieldProvider
{
    public function __construct(
        private readonly BulkBasePrice $base,
        private readonly PriceListService $lists,
    ) {}

    public function code(): string
    {
        return 'pricing';
    }

    public function label(): string
    {
        return __('pricing::labels.matrix.label');
    }

    public function fields(): array
    {
        return [
            FieldDefinition::int('price', __('pricing::labels.matrix.price')),
            FieldDefinition::int('compare_at', __('pricing::labels.matrix.compare_at'), help: __('pricing::labels.matrix.compare_at_help')),
        ];
    }

    public function permission(): string
    {
        return 'pricing.manage';
    }

    public function values(int $styleId, array $variantIds): array
    {
        return DB::table('prices')->where('price_list_id', $this->base->baseList()->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->variant_id => ['price' => (int) $row->amount, 'compare_at' => $row->compare_at_amount === null ? null : (int) $row->compare_at_amount]])
            ->all();
    }

    public function save(int $styleId, array $values): array
    {
        $current = $this->values($styleId, array_keys($values));
        $rows = [];
        $errors = [];
        foreach ($values as $variantId => $changes) {
            $amount = array_key_exists('price', $changes) ? $this->amount($changes['price']) : ($current[$variantId]['price'] ?? null);
            $compareAt = array_key_exists('compare_at', $changes) ? $this->amount($changes['compare_at']) : ($current[$variantId]['compare_at'] ?? null);
            if (($amount !== null && $amount < 0) || ($compareAt !== null && $compareAt < 0)) {
                $errors[$variantId] = __('pricing::messages.amount_negative');
            } elseif ($amount !== null && $compareAt !== null && $compareAt <= $amount) {
                $errors[$variantId] = __('pricing::messages.compare_at_invalid');
            } else {
                $rows[] = ['variant_id' => $variantId, 'amount' => $amount, 'compare_at_amount' => $amount === null ? null : $compareAt];
            }
        }
        if ($rows !== []) {
            try {
                $this->lists->setPrices($this->base->baseList(), $rows);
            } catch (ValidationException $exception) {
                foreach ($rows as $row) {
                    $errors[$row['variant_id']] = (string) collect($exception->errors())->flatten()->first();
                }
            }
        }

        return $errors;
    }

    public function storefront(array $variantIds): array
    {
        return []; // Giá đã có ở `variants[].price` (theo bảng giá, khuyến mãi, nhóm khách).
    }

    private function amount(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }
}
