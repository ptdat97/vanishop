<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\Data\SyncOutcome;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Inventory\Persistence\Models\InventoryReconciliation;
use Modules\Inventory\Persistence\Models\InventoryReconciliationLine;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Ghi kết quả đối soát tồn kho vào bảng chung (roadmap Phase 3):
 * - nội bộ: kết quả của `vani:inventory:verify` (trước đây chỉ có output lệnh + log);
 * - nguồn ngoài: snapshot của authority so với `on_hand` của VaniShop.
 *
 * Không bao giờ tự sửa phía ngoài; chỉ áp số lên phía VaniShop khi location do chính
 * nguồn đó quản lý tồn (`locations.stock_authority === source` và khác `vanishop`).
 * Với location khác, chênh lệch chỉ được ghi lại để xử lý tay.
 */
final class InventoryReconciler
{
    private const MAX_DETAILS = 100;

    public function __construct(
        private readonly InventorySync $sync,
        private readonly VariantDirectory $variants,
        private readonly CurrentContext $context,
    ) {}

    /**
     * Lưu kết quả của StockVerifier::verify() thành một phiên đối soát nội bộ.
     *
     * @param  array{checked: int, issues: list<array{location_id: int, variant_id: int, issue: string, expected: int, actual: int, repaired: bool}>, repaired: int}  $result
     */
    public function recordVerifyResult(array $result): int
    {
        return $this->context->runAs(ContextScope::system('vani:inventory:verify reconcile'), function () use ($result): int {
            return DB::transaction(function () use ($result): int {
                $runId = (int) DB::table('inventory_reconciliations')->insertGetId([
                    'source' => InventoryReconciliation::SOURCE_INTERNAL_VERIFY,
                    'checked' => $result['checked'],
                    'discrepancies' => count($result['issues']),
                    'repaired' => $result['repaired'],
                    'details' => json_encode(array_map(fn (array $issue): array => array_diff_key($issue, ['repaired' => true]),
                        array_slice($result['issues'], 0, self::MAX_DETAILS)), JSON_UNESCAPED_UNICODE),
                    'started_at' => now(),
                    'finished_at' => now(),
                ]);

                foreach ($result['issues'] as $issue) {
                    DB::table('inventory_reconciliation_lines')->insert([
                        'reconciliation_id' => $runId,
                        'location_id' => $issue['location_id'],
                        'variant_id' => $issue['variant_id'],
                        'classification' => $issue['issue'],
                        'expected' => $issue['expected'],
                        'actual' => $issue['actual'],
                        'difference' => $issue['expected'] - $issue['actual'],
                        'detected_at' => now(),
                        'resolved_at' => $issue['repaired'] ? now() : null,
                        'resolution' => $issue['repaired'] ? InventoryReconciliationLine::RESOLUTION_REPAIRED : null,
                    ]);
                }

                if ($result['issues'] !== []) {
                    Log::warning('Đối soát tồn nội bộ có chênh lệch.', ['run_id' => $runId, 'discrepancies' => count($result['issues']), 'repaired' => $result['repaired']]);
                }

                return $runId;
            }, attempts: 3);
        });
    }

    /**
     * Đối soát với một nguồn ngoài bằng snapshot: `location` (mã kho), `sku` hoặc `variant_id`,
     * `on_hand` (số tuyệt đối), `version` (tăng dần do nguồn ngoài cấp).
     *
     * Chỉ áp số lên tồn VaniShop khi location do chính `$source` quản lý tồn
     * (`stock_authority === $source`, không phải `vanishop`). Snapshot cũ hơn bản đã nhận
     * (`sync_version` cao hơn) bị bỏ qua; chênh lệch với location khác chỉ được ghi để xử lý tay.
     *
     * @param  string  $source  mã authority ngoài (vd. `client_erp`), là `source` của phiên đối soát.
     * @param  list<array{location?: string, sku?: string, variant_id?: int, on_hand?: int, version?: int}>  $rows
     * @return array{id: int, checked: int, discrepancies: int, repaired: int, skipped: int}
     */
    public function reconcileExternal(string $source, array $rows, bool $apply = true): array
    {
        return $this->context->runAs(ContextScope::system("inventory reconcile {$source}"), function () use ($source, $rows, $apply): array {
            $runId = (int) DB::table('inventory_reconciliations')->insertGetId([
                'source' => $source, 'started_at' => now(),
            ]);

            // Nhóm theo (location, variant) lấy bản mới nhất; bỏ dòng không tra được.
            $best = $this->bestRows($source, $rows);

            $checked = $discrepancies = $repaired = $skipped = 0;
            $details = [];
            $lines = [];

            foreach ($best as $row) {
                $current = DB::table('stock_levels')->where(['location_id' => $row['location_id'], 'variant_id' => $row['variant_id']])->first();
                $actual = $current === null ? 0 : (int) $current->on_hand;
                // Snapshot cũ hơn bản đã nhận từ nguồn này → bỏ qua, không tạo nhiễu.
                if ($current !== null && $current->sync_version !== null && (int) $current->sync_version > $row['version']) {
                    $skipped++;

                    continue;
                }
                $checked++;
                if ($actual === $row['on_hand']) {
                    continue;
                }
                $discrepancies++;

                $resolution = null;
                $note = null;
                if ($apply && ! $row['location']->managesOnHand() && $row['location']->stock_authority === $source) {
                    try {
                        $outcome = $this->sync->syncOnHand($source, $row['location']->code, $row['variant_id'], $row['on_hand'], $row['version'], 'external_reconcile');
                        if ($outcome === SyncOutcome::Stale) {
                            // Giữa lúc đọc và lúc ghi đã có bản mới hơn — không phải chênh lệch thật.
                            $discrepancies--;
                            $skipped++;

                            continue;
                        }
                        // Applied hoặc Unchanged — số nguồn đã phản ánh đúng trên tồn VaniShop.
                        $resolution = InventoryReconciliationLine::RESOLUTION_APPLIED;
                    } catch (Throwable $exception) {
                        $resolution = InventoryReconciliationLine::RESOLUTION_REJECTED;
                        $note = $exception::class.': '.$exception->getMessage();
                        Log::warning('Đối soát nguồn ngoài: không áp được số lên tồn.', [
                            'run_id' => $runId, 'source' => $source, 'location' => $row['location']->code,
                            'variant_id' => $row['variant_id'], 'exception' => $exception,
                        ]);
                    }
                }
                if ($resolution === InventoryReconciliationLine::RESOLUTION_APPLIED) {
                    $repaired++;
                }

                $lines[] = [
                    'reconciliation_id' => $runId,
                    'location_id' => $row['location_id'],
                    'variant_id' => $row['variant_id'],
                    'classification' => InventoryReconciliationLine::CLASSIFICATION_EXTERNAL_MISMATCH,
                    'expected' => $row['on_hand'],
                    'actual' => $actual,
                    'difference' => $row['on_hand'] - $actual,
                    'note' => $note,
                    'detected_at' => now(),
                    'resolved_at' => $resolution === null ? null : now(),
                    'resolution' => $resolution,
                ];
                if (count($details) < self::MAX_DETAILS) {
                    $details[] = [
                        'location' => $row['location']->code,
                        'sku' => $row['sku'],
                        'expected' => $row['on_hand'],
                        'actual' => $actual,
                    ];
                }
            }

            if ($lines !== []) {
                DB::table('inventory_reconciliation_lines')->insert($lines);
            }
            DB::table('inventory_reconciliations')->where('id', $runId)->update([
                'checked' => $checked, 'discrepancies' => $discrepancies, 'repaired' => $repaired,
                'details' => json_encode($details, JSON_UNESCAPED_UNICODE), 'finished_at' => now(),
            ]);

            if ($discrepancies > 0) {
                Log::warning('Đối soát tồn với nguồn ngoài có chênh lệch.', [
                    'run_id' => $runId, 'source' => $source, 'discrepancies' => $discrepancies, 'repaired' => $repaired, 'skipped' => $skipped,
                ]);
            }

            return ['id' => $runId, 'checked' => $checked, 'discrepancies' => $discrepancies, 'repaired' => $repaired, 'skipped' => $skipped];
        });
    }

    /**
     * Tra location/variant, lọc bỏ dòng không hợp lệ và chỉ giữ bản mới nhất theo version
     * của mỗi (location, variant).
     *
     * @param  list<array{location?: string, sku?: string, variant_id?: int, on_hand?: int, version?: int}>  $rows
     * @return list<array{location_id: int, location: Location, variant_id: int, sku: string|null, on_hand: int, version: int}>
     */
    private function bestRows(string $source, array $rows): array
    {
        $locations = Location::query()->get()->keyBy('code');
        $skus = array_values(array_unique(array_filter(array_map(fn (array $row): string => isset($row['sku']) ? (string) $row['sku'] : '', $rows))));
        $found = $skus === [] ? [] : $this->variants->findBySkus($skus);

        $best = [];
        foreach ($rows as $row) {
            $code = (string) ($row['location'] ?? '');
            $location = $locations[$code] ?? null;
            if ($location === null) {
                Log::warning('Đối soát nguồn ngoài: bỏ qua location không tồn tại.', ['source' => $source, 'location' => $code]);

                continue;
            }
            $variantId = isset($row['variant_id']) ? (int) $row['variant_id']
                : (isset($row['sku'], $found[$row['sku']]) ? (int) $found[$row['sku']]->id : 0);
            $sku = isset($row['variant_id']) ? null : (string) ($row['sku'] ?? '');
            if ($variantId <= 0) {
                Log::warning('Đối soát nguồn ngoài: bỏ qua variant không tìm thấy.', ['source' => $source, 'location' => $code, 'sku' => $sku]);

                continue;
            }
            $onHand = (int) ($row['on_hand'] ?? 0);
            $version = (int) ($row['version'] ?? 0);
            if ($onHand < 0 || $version < 0) {
                Log::warning('Đối soát nguồn ngoài: bỏ qua dòng số liệu sai.', ['source' => $source, 'location' => $code, 'on_hand' => $onHand, 'version' => $version]);

                continue;
            }

            $key = "{$location->id}:{$variantId}";
            if (! isset($best[$key]) || $best[$key]['version'] < $version) {
                $best[$key] = [
                    'location_id' => (int) $location->id,
                    'location' => $location,
                    'variant_id' => $variantId,
                    'sku' => $sku,
                    'on_hand' => $onHand,
                    'version' => $version,
                ];
            }
        }

        return array_values($best);
    }
}
