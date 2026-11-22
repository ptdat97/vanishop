<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Domain\MovementType;
use Modules\Inventory\Domain\StockLevel;
use Modules\Inventory\Persistence\Models\StockLevelRecord;
use Modules\Inventory\Persistence\Models\StockMovement;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;

/**
 * Đọc/ghi dòng tồn có khoá và ghi sổ biến động. Chỉ dùng BÊN TRONG transaction.
 */
final class StockLedger
{
    public const DEFAULT_SAFETY_STOCK = 'inventory.default_safety_stock';

    public function __construct(private readonly CurrentContext $context, private readonly Settings $settings) {}

    /**
     * Khoá (FOR UPDATE) các dòng tồn theo thứ tự (location_id, variant_id) cố định để tránh deadlock.
     * Dòng chưa tồn tại được tạo với tồn thực tế và số giữ bằng 0 trước khi khoá.
     *
     * @param  list<array{0: int, 1: int}>  $pairs  [location_id, variant_id]
     * @return array<string, StockLevelRecord> "loc:variant" => record
     */
    public function lock(array $pairs, bool $createMissing = false): array
    {
        if ($pairs === []) {
            return [];
        }

        if ($createMissing) {
            $this->ensure($pairs);
        }

        $locationIds = array_values(array_unique(array_column($pairs, 0)));
        $variantIds = array_values(array_unique(array_column($pairs, 1)));

        return StockLevelRecord::query()
            ->whereIn('location_id', $locationIds)
            ->whereIn('variant_id', $variantIds)
            ->orderBy('location_id')
            ->orderBy('variant_id')
            ->lockForUpdate()
            ->get()
            ->keyBy(fn (StockLevelRecord $record): string => $record->location_id.':'.$record->variant_id)
            ->all();
    }

    /**
     * Khởi tạo tồn cho cặp [location, variant] chưa có bằng mức an toàn mặc định của cửa hàng.
     *
     * @param  list<array{0: int, 1: int}>  $pairs
     */
    public function ensure(array $pairs): void
    {
        if ($pairs === []) {
            return;
        }
        $safetyStock = max(0, (int) $this->settings->get('core', self::DEFAULT_SAFETY_STOCK, 0));
        DB::table('stock_levels')->insertOrIgnore(array_map(fn (array $pair): array => [
            'location_id' => $pair[0], 'variant_id' => $pair[1], 'on_hand' => 0, 'reserved' => 0, 'safety_stock' => $safetyStock,
            'created_at' => now(), 'updated_at' => now(),
        ], $pairs));
    }

    public function save(StockLevelRecord $record, StockLevel $after, MovementType $type, ?string $reason = null, ?string $reference = null): void
    {
        $before = $record->toDomain();
        $record->fillFromDomain($after)->save();

        $actor = $this->context->has() ? $this->context->actor() : null;

        StockMovement::query()->create([
            'location_id' => $after->locationId,
            'variant_id' => $after->variantId,
            'type' => $type,
            'on_hand_delta' => $after->onHand - $before->onHand,
            'reserved_delta' => $after->reserved - $before->reserved,
            'on_hand_after' => $after->onHand,
            'reserved_after' => $after->reserved,
            'reason' => $reason,
            'reference' => $reference,
            'actor_type' => $actor?->type->value,
            'actor_id' => $actor?->id,
            'correlation_id' => Context::get('correlation_id'),
            'created_at' => now(),
        ]);
    }
}
