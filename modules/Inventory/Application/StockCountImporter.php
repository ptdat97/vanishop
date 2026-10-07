<?php

declare(strict_types=1);

namespace Modules\Inventory\Application;

use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\StockImporter;
use Modules\Inventory\Persistence\Models\Location;

final class StockCountImporter implements StockImporter
{
    public function __construct(
        private readonly StockAdjustmentService $stock,
        private readonly VariantDirectory $variants,
    ) {}

    public function setOnHand(array $quantities, ?string $locationCode, string $reason): array
    {
        $location = $locationCode !== null
            ? Location::query()->where('code', $locationCode)->first()
            : Location::query()->where('status', 'active')->where('ships_online_orders', true)->where('stock_authority', Location::AUTHORITY_VANISHOP)
                ->orderByDesc('priority')->orderBy('id')->first();
        if ($location === null) {
            return ['location' => null, 'updated' => 0, 'unknown' => []];
        }

        $variants = $this->variants->findBySkus(array_map('strval', array_keys($quantities)));
        $updated = 0;
        $unknown = [];
        foreach ($quantities as $sku => $onHand) {
            $variant = $variants[(string) $sku] ?? null;
            if ($variant === null) {
                $unknown[] = (string) $sku;

                continue;
            }
            $this->stock->count($location, $variant->id, max(0, $onHand), $reason); // chặn kho authority ngoài
            $updated++;
        }

        return ['location' => $location->code, 'updated' => $updated, 'unknown' => $unknown];
    }
}
