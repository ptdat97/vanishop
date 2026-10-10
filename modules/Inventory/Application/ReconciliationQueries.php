<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Persistence\Models\InventoryReconciliation;
use Modules\Inventory\Persistence\Models\InventoryReconciliationLine;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Shared\Support\StoreClock;

/**
 * Truy vấn đọc cho màn hình "Đối soát tồn kho" trong Admin (roadmap Phase 3).
 */
final class ReconciliationQueries
{
    public function __construct(private readonly VariantDirectory $variants) {}

    /**
     * @return list<array<string, mixed>>
     */
    public function recent(int $limit = 100): array
    {
        return InventoryReconciliation::query()->orderByDesc('id')->limit($limit)->get()
            ->map(fn (InventoryReconciliation $run): array => $this->run($run))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function run(InventoryReconciliation $run): array
    {
        return [
            'id' => $run->id,
            'source' => $run->source,
            'source_label' => $run->source === InventoryReconciliation::SOURCE_INTERNAL_VERIFY
                ? 'Đối soát nội bộ (vani:inventory:verify)'
                : "Nguồn ngoài: {$run->source}",
            'checked' => $run->checked,
            'discrepancies' => $run->discrepancies,
            'repaired' => $run->repaired,
            'open' => $run->discrepancies - $run->repaired,
            'started_at' => $this->formatDate($run->started_at),
            'finished_at' => $this->formatDate($run->finished_at),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function lines(int $reconciliationId): array
    {
        $rows = InventoryReconciliationLine::query()->where('reconciliation_id', $reconciliationId)->orderBy('id')->get();
        $variants = $this->variants->find($rows->pluck('variant_id')->all());
        $locations = Location::query()->pluck('code', 'id');

        return $rows->map(fn (InventoryReconciliationLine $line): array => [
            'id' => $line->id,
            'location' => $locations[$line->location_id] ?? (string) $line->location_id,
            'sku' => $variants[$line->variant_id]->sku ?? '#'.$line->variant_id,
            'classification' => $line->classification,
            'expected' => $line->expected,
            'actual' => $line->actual,
            'difference' => $line->difference,
            'note' => $line->note,
            'detected_at' => $this->formatDate($line->detected_at),
            'resolved_at' => $this->formatDate($line->resolved_at),
            'resolution' => $line->resolution,
        ])->all();
    }

    private function formatDate(mixed $date): ?string
    {
        return StoreClock::format($date);
    }
}
