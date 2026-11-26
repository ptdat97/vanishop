<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Support\DurableCache;

/**
 * Nhập/xuất tồn kho bằng CSV trong Admin. File xuất mở được bằng Excel (UTF-8 BOM) và nhập lại được: cột bắt buộc
 * `sku`, `on_hand`; cột `location` (mã kho) tuỳ chọn — thiếu thì dùng kho chọn khi nhập. Cột khác bị bỏ qua.
 *
 * Nhập hai bước: xem trước (so với tồn hiện tại, lưu tạm 30 phút theo token) → áp như kiểm kê; dòng mà tồn đã đổi sau
 * khi xem trước bị bỏ qua (xung đột), không ghi đè.
 */
final class StockCsv
{
    /** Trần an toàn của một file (đọc vào bộ nhớ); quá `StockBulkRun::SYNC_LIMIT` dòng đổi thì ghi chạy nền (L20). */
    public const MAX_ROWS = 50000;

    /** Số dòng trả về trang xem trước (ưu tiên dòng lỗi/đổi); tổng theo trạng thái vẫn tính trên mọi dòng. */
    public const PREVIEW_LINES = 1000;

    private const CACHE_PREFIX = 'inventory:stock-import:';

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
        private readonly StockAdjustmentService $stock,
        private readonly StockBulkRun $runs,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return \Generator<int, list<string|int>>
     */
    public function export(?Location $only): \Generator
    {
        $locations = array_values(array_filter($this->queries->locations(), fn (Location $location): bool => $only === null || $location->id === $only->id));
        yield ['sku', 'location', 'on_hand', 'reserved', 'safety_stock', 'available', 'product', 'options'];

        // Mọi variant theo thứ tự của lưới tồn, đọc theo lô 500 từ read model (không nạp cả danh sách vào bộ nhớ — db-refactor G3).
        $lastKey = null;
        while (true) {
            $ids = DB::table('stock_grid_index')
                ->when($lastKey !== null, fn ($query) => $query->where(fn ($q) => $q->where('name_sort', '>', $lastKey[0])
                    ->orWhere(fn ($q) => $q->where('name_sort', $lastKey[0])->where('product_code', '>', $lastKey[1]))
                    ->orWhere(fn ($q) => $q->where('name_sort', $lastKey[0])->where('product_code', $lastKey[1])->where('product_id', '>', $lastKey[2]))
                    ->orWhere(fn ($q) => $q->where('name_sort', $lastKey[0])->where('product_code', $lastKey[1])->where('product_id', $lastKey[2])->where('variant_rank', '>', $lastKey[3]))))
                ->orderBy('name_sort')->orderBy('product_code')->orderBy('product_id')->orderBy('variant_rank')
                ->limit(500)->get(['variant_id', 'name_sort', 'product_code', 'product_id', 'variant_rank']);
            if ($ids->isEmpty()) {
                break;
            }
            $last = $ids->last();
            $lastKey = [$last->name_sort, $last->product_code, $last->product_id, $last->variant_rank];
            $ids = $ids->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();
            $levels = $this->queries->levels($ids);
            $chunk = $this->variants->find($ids);
            foreach ($ids as $id) {
                $variant = $chunk[$id] ?? null;
                if ($variant === null) {
                    continue;
                }
                foreach ($locations as $location) {
                    $level = $levels[$location->id.':'.$variant->id] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0];
                    yield [$variant->sku, $location->code, $level['on_hand'], $level['reserved'], $level['safety_stock'], $level['available'], $variant->productName, VariantOptionsText::describe($variant->options)];
                }
            }
        }
    }

    /**
     * Đọc file + so với tồn hiện tại. Trả token (null khi không có dòng nào cần đổi) và các dòng để xem trước.
     *
     * @return array{token: ?string, file: string, lines: list<array{line: int, sku: string, location: string, current: ?int, new: ?int, status: string}>, summary: array<string, int>}
     */
    public function preview(string $path, string $fileName, ?Location $defaultLocation): array
    {
        $rows = $this->read($path);
        $locations = collect($this->queries->locations())->keyBy('code');
        $variants = $this->variants->findBySkus(array_values(array_unique(array_map(fn (array $row): string => $row['sku'], $rows))));
        $levels = $variants === [] ? [] : $this->queries->levels(array_values(array_map(fn (VariantData $variant): int => $variant->id, $variants)));

        $lines = [];
        $changes = [];
        $seen = [];
        foreach ($rows as $row) {
            $location = $row['location'] !== '' ? $locations->get($row['location']) : $defaultLocation;
            $variant = $variants[$row['sku']] ?? null;
            $current = $location !== null && $variant !== null ? ($levels[$location->id.':'.$variant->id]['on_hand'] ?? 0) : null;
            $status = match (true) {
                $row['on_hand'] === null => 'invalid',
                $variant === null => 'unknown_sku',
                $location === null => 'unknown_location',
                ! $location->managesOnHand() => 'external',
                isset($seen[$location->id.':'.$variant->id]) => 'duplicate',
                $current === $row['on_hand'] => 'same',
                default => 'change',
            };
            if ($location !== null && $variant !== null) {
                $seen[$location->id.':'.$variant->id] = true;
            }
            if ($status === 'change') {
                $changes[] = ['location_id' => $location->id, 'variant_id' => $variant->id, 'on_hand' => $row['on_hand'], 'expected' => $current];
            }
            $lines[] = ['line' => $row['line'], 'sku' => $row['sku'], 'location' => $location->code ?? $row['location'], 'current' => $current, 'new' => $row['on_hand'], 'status' => $status];
        }

        $token = null;
        if ($changes !== []) {
            $token = Str::random(40);
            DurableCache::store()->put(self::CACHE_PREFIX.$token, ['file' => $fileName, 'changes' => $changes], now()->addMinutes(30));
        }

        // File lớn: trang xem trước chỉ hiện dòng cần chú ý trước (lỗi, đổi), tối đa PREVIEW_LINES; tổng vẫn đủ.
        $rank = ['invalid' => 0, 'unknown_sku' => 0, 'unknown_location' => 0, 'external' => 0, 'duplicate' => 0, 'change' => 1, 'same' => 2];
        $shown = $lines;
        if (count($shown) > self::PREVIEW_LINES) {
            usort($shown, fn (array $a, array $b): int => [$rank[$a['status']] ?? 3, $a['line']] <=> [$rank[$b['status']] ?? 3, $b['line']]);
        }

        return [
            'token' => $token, 'file' => $fileName, 'lines' => array_slice($shown, 0, self::PREVIEW_LINES),
            'hidden_lines' => max(0, count($lines) - self::PREVIEW_LINES),
            'summary' => array_count_values(array_column($lines, 'status')),
        ];
    }

    /**
     * @return array{file: string, changes: list<array{location_id: int, variant_id: int, on_hand: int, expected: int}>}|null
     */
    public function pending(string $token): ?array
    {
        $data = DurableCache::store()->get(self::CACHE_PREFIX.$token);

        return is_array($data) ? $data : null;
    }

    /**
     * Áp các dòng đã xem trước. Quá `StockBulkRun::SYNC_LIMIT` dòng → chia lô chạy nền, trả `run` (id lượt chạy) thay vì kết quả.
     *
     * @return array{updated: int, conflicts: int, run: string|null}
     */
    public function apply(string $token): array
    {
        $data = DurableCache::store()->pull(self::CACHE_PREFIX.$token);
        if (! is_array($data)) {
            throw ValidationException::withMessages(['business' => __('inventory::messages.import_expired')]);
        }
        if (count($data['changes']) > StockBulkRun::SYNC_LIMIT) {
            $scope = $this->context->scope();
            $jobs = array_map(fn (array $chunk): ApplyStockChunkJob => new ApplyStockChunkJob('import', ['file' => $data['file'], 'changes' => $chunk], $scope), array_chunk($data['changes'], StockBulkRun::CHUNK));

            return ['updated' => 0, 'conflicts' => 0, 'run' => $this->runs->dispatch(__('inventory::messages.import_run_label', ['file' => $data['file']]), $jobs)];
        }

        return [...$this->applyChanges((string) $data['file'], $data['changes']), 'run' => null];
    }

    /**
     * Ghi một lô dòng (như kiểm kê; tồn đã đổi sau khi xem trước → xung đột, không ghi đè).
     *
     * @param  array<int, mixed>  $changes
     * @return array{updated: int, conflicts: int}
     */
    public function applyChanges(string $file, array $changes): array
    {
        $data = ['file' => $file];
        $locations = collect($this->queries->locations())->keyBy('id');
        $result = ['updated' => 0, 'conflicts' => 0];
        foreach ($changes as $change) {
            $location = $locations->get($change['location_id']);
            if ($location === null || ! $location->managesOnHand()) {
                $result['conflicts']++;

                continue;
            }
            $outcome = $this->stock->count($location, $change['variant_id'], $change['on_hand'], __('inventory::messages.import_reason', ['file' => $data['file']]), $change['expected']);
            if ($outcome === StockAdjustmentService::CONFLICT) {
                $result['conflicts']++;
            } elseif ($outcome === StockAdjustmentService::UPDATED) {
                $result['updated']++;
            }
        }

        return $result;
    }

    /**
     * @return list<array{line: int, sku: string, location: string, on_hand: ?int}>
     */
    private function read(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages(['file' => __('inventory::messages.import_unreadable')]);
        }
        $first = (string) fgets($handle);
        $first = preg_replace('/^\xEF\xBB\xBF/', '', $first) ?? $first;
        $delimiter = substr_count($first, ';') > substr_count($first, ',') ? ';' : ',';
        $header = array_map(fn (?string $cell): string => strtolower(trim((string) $cell)), str_getcsv($first, $delimiter, '"', ''));
        $columns = ['sku' => array_search('sku', $header, true), 'on_hand' => array_search('on_hand', $header, true), 'location' => array_search('location', $header, true)];
        if ($columns['sku'] === false || $columns['on_hand'] === false) {
            fclose($handle);
            throw ValidationException::withMessages(['file' => __('inventory::messages.import_header')]);
        }

        $rows = [];
        $line = 1;
        while (($cells = fgetcsv($handle, null, $delimiter, '"', '')) !== false) {
            $line++;
            $sku = trim((string) ($cells[$columns['sku']] ?? ''));
            if ($sku === '') {
                continue;
            }
            if (count($rows) >= self::MAX_ROWS) {
                fclose($handle);
                throw ValidationException::withMessages(['file' => __('inventory::messages.import_too_many', ['max' => self::MAX_ROWS])]);
            }
            $quantity = trim((string) ($cells[$columns['on_hand']] ?? ''));
            $rows[] = [
                'line' => $line, 'sku' => $sku,
                'location' => $columns['location'] === false ? '' : strtoupper(trim((string) ($cells[$columns['location']] ?? ''))),
                'on_hand' => preg_match('/^\d{1,7}$/', $quantity) === 1 ? (int) $quantity : null,
            ];
        }
        fclose($handle);

        return $rows;
    }
}
