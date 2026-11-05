<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\Data\VariantData;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\Location;

/**
 * Nhập/xuất tồn kho bằng CSV trong Admin. File xuất mở được bằng Excel (UTF-8 BOM) và nhập lại được: cột bắt buộc
 * `sku`, `on_hand`; cột `location` (mã kho) tuỳ chọn — thiếu thì dùng kho chọn khi nhập. Cột khác bị bỏ qua.
 *
 * Nhập hai bước: xem trước (so với tồn hiện tại, lưu tạm 30 phút theo token) → áp như kiểm kê; dòng mà tồn đã đổi sau
 * khi xem trước bị bỏ qua (xung đột), không ghi đè.
 */
final class StockCsv
{
    public const MAX_ROWS = 5000;

    private const CACHE_PREFIX = 'inventory:stock-import:';

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly StockQueries $queries,
        private readonly StockAdjustmentService $stock,
    ) {}

    /**
     * @return \Generator<int, list<string|int>>
     */
    public function export(?Location $only): \Generator
    {
        $locations = array_values(array_filter($this->queries->locations(), fn (Location $location): bool => $only === null || $location->id === $only->id));
        yield ['sku', 'location', 'on_hand', 'reserved', 'safety_stock', 'available', 'product', 'color', 'size'];

        $variants = $this->variants->search('', StockGrid::MAX_VARIANTS);
        foreach (array_chunk($variants, 500) as $chunk) {
            $levels = $this->queries->levels(array_map(fn (VariantData $variant): int => $variant->id, $chunk));
            foreach ($chunk as $variant) {
                foreach ($locations as $location) {
                    $level = $levels[$location->id.':'.$variant->id] ?? ['on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'available' => 0];
                    yield [$variant->sku, $location->code, $level['on_hand'], $level['reserved'], $level['safety_stock'], $level['available'], $variant->styleName, $variant->colorCode, $variant->sizeCode];
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
            Cache::put(self::CACHE_PREFIX.$token, ['file' => $fileName, 'changes' => $changes], now()->addMinutes(30));
        }

        return ['token' => $token, 'file' => $fileName, 'lines' => $lines, 'summary' => array_count_values(array_column($lines, 'status'))];
    }

    /**
     * @return array{file: string, changes: list<array{location_id: int, variant_id: int, on_hand: int, expected: int}>}|null
     */
    public function pending(string $token): ?array
    {
        $data = Cache::get(self::CACHE_PREFIX.$token);

        return is_array($data) ? $data : null;
    }

    /**
     * @return array{updated: int, conflicts: int}
     */
    public function apply(string $token): array
    {
        $data = Cache::pull(self::CACHE_PREFIX.$token);
        if (! is_array($data)) {
            throw ValidationException::withMessages(['token' => __('inventory::messages.import_expired')]);
        }
        $locations = collect($this->queries->locations())->keyBy('id');
        $result = ['updated' => 0, 'conflicts' => 0];
        foreach ($data['changes'] as $change) {
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
