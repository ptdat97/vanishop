<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Cột "Tồn kho" của ma trận biến thể (variant-matrix §3.3): tồn thực tế ở kho duy nhất của cửa hàng (lưu như kiểm kê). Cửa
 * hàng nhiều kho hoặc kho do hệ thống ngoài quản lý → không có cột (sửa ở lưới tồn). SKU có biến động/tồn/đối soát là "đã
 * phát sinh"; SKU sắp xoá → dọn dòng tồn bằng 0.
 */
final class VariantStockFields implements VariantFieldProvider
{
    public function __construct(
        private readonly StockQueries $queries,
        private readonly StockAdjustmentService $stock,
    ) {}

    public function code(): string
    {
        return 'inventory';
    }

    public function label(): string
    {
        return __('inventory::labels.matrix.label');
    }

    public function fields(): array
    {
        $location = $this->location();

        return $location === null ? [] : [FieldDefinition::int('on_hand', __('inventory::labels.matrix.on_hand', ['location' => $location->name]))];
    }

    public function permission(): string
    {
        return 'inventory.adjust';
    }

    public function values(int $styleId, array $variantIds): array
    {
        $location = $this->location();
        if ($location === null) {
            return [];
        }

        return DB::table('stock_levels')->where('location_id', $location->id)->whereIn('variant_id', $variantIds)->pluck('on_hand', 'variant_id')
            ->map(fn ($onHand): array => ['on_hand' => (int) $onHand])->all();
    }

    public function save(int $styleId, array $values): array
    {
        $location = $this->location();
        if ($location === null) {
            return [];
        }
        $errors = [];
        foreach ($values as $variantId => $changes) {
            if (! array_key_exists('on_hand', $changes) || $changes['on_hand'] === null || $changes['on_hand'] === '') {
                continue;
            }
            try {
                $this->stock->count($location, $variantId, (int) $changes['on_hand'], __('inventory::labels.matrix.reason'));
            } catch (ValidationException $exception) {
                $errors[$variantId] = (string) collect($exception->errors())->flatten()->first();
            }
        }

        return $errors;
    }

    public function storefront(array $variantIds): array
    {
        return []; // Trạng thái hàng đã có ở `variants[].availability`; không công bố số tồn.
    }

    private function location(): ?Location
    {
        $locations = $this->queries->locations();

        return count($locations) === 1 && $locations[0]->managesOnHand() && $locations[0]->status === 'active' ? $locations[0] : null;
    }
}
