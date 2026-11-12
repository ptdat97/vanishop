<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Sửa tồn hàng loạt tại một kho cho các variant đã chọn (theo SKU, cả sản phẩm hoặc mọi kết quả lọc): đặt số tồn, cộng/trừ,
 * đặt tồn an toàn. Mỗi variant là một biến động riêng trong sổ (như sửa từng ô); variant không áp được (vd. trừ quá tồn)
 * được bỏ qua và đếm lại, không làm hỏng cả lô.
 */
final class StockBulkEditor
{
    public const ACTIONS = ['set', 'adjust', 'safety'];

    public const MAX_VARIANTS = StockGrid::MAX_BULK_VARIANTS;

    public function __construct(
        private readonly StockAdjustmentService $stock,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<int>  $variantIds
     * @param  'set'|'adjust'|'safety'  $action
     * @return array{updated: int, unchanged: int, skipped: int}
     */
    public function apply(Location $location, array $variantIds, string $action, int $quantity, string $reason): array
    {
        if ($action !== 'safety' && ! $location->managesOnHand()) {
            throw ValidationException::withMessages(['location_id' => __('inventory::messages.external_authority', ['authority' => $location->stock_authority])]);
        }

        $result = ['updated' => 0, 'unchanged' => 0, 'skipped' => 0];
        foreach (array_values(array_unique($variantIds)) as $variantId) {
            try {
                $changed = match ($action) {
                    'set' => $this->stock->count($location, $variantId, $quantity, $reason) === StockAdjustmentService::UPDATED,
                    'adjust' => $this->adjust($location, $variantId, $quantity, $reason),
                    'safety' => $this->stock->setSafetyStock($location, $variantId, $quantity),
                };
                $result[$changed ? 'updated' : 'unchanged']++;
            } catch (ValidationException) {
                $result['skipped']++;
            }
        }

        $this->audit->record('inventory.stock.bulk', 'location', $location->id, ['location' => $location->code, 'action' => $action, 'quantity' => $quantity, 'reason' => $reason, 'variants' => count($variantIds), ...$result]);

        return $result;
    }

    private function adjust(Location $location, int $variantId, int $delta, string $reason): bool
    {
        if ($delta === 0) {
            return false;
        }
        $this->stock->adjust($location, $variantId, $delta, $reason);

        return true;
    }
}
