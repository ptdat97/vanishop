<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Inventory\Contracts\Data\IncomingStockData;
use Modules\Inventory\Contracts\IncomingStock;

/**
 * Hàng đang về theo SKU (bảng `incoming_stock`, ADR-047).
 */
final class IncomingStockService implements IncomingStock
{
    public function set(int $variantId, int $quantity, ?string $expectedOn, string $source): void
    {
        if ($quantity <= 0) {
            DB::table('incoming_stock')->where('variant_id', $variantId)->delete();

            return;
        }
        if ($expectedOn !== null && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $expectedOn)) {
            throw new InvalidArgumentException("Ngày dự kiến [{$expectedOn}] phải theo dạng Y-m-d.");
        }

        // upsert: tạo mới thì có created_at; cập nhật chỉ đổi số lượng/ngày/nguồn và updated_at (giữ ngày tạo).
        DB::table('incoming_stock')->upsert(
            [['variant_id' => $variantId, 'quantity' => $quantity, 'expected_on' => $expectedOn, 'source' => mb_substr($source, 0, 64), 'created_at' => now(), 'updated_at' => now()]],
            ['variant_id'],
            ['quantity', 'expected_on', 'source', 'updated_at'],
        );
    }

    public function forVariants(array $variantIds): array
    {
        if ($variantIds === []) {
            return [];
        }

        return DB::table('incoming_stock')->whereIn('variant_id', $variantIds)->get()
            ->mapWithKeys(fn (object $row): array => [(int) $row->variant_id => new IncomingStockData(
                (int) $row->variant_id, (int) $row->quantity, $row->expected_on === null ? null : substr((string) $row->expected_on, 0, 10), (string) $row->source,
            )])->all();
    }
}
