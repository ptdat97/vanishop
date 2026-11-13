<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Facades\App;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Returns\Contracts\Returns;

/**
 * Dữ liệu form đổi/trả trên storefront native (order §7, §7.1): dòng còn trả được + lựa chọn đổi size/màu CÙNG MẪU
 * (variant đang bán, kèm còn hàng hay không). Đổi sang mẫu khác qua CSKH (Admin), không có trên form khách.
 */
final class ReturnFormPresenter
{
    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly CatalogReader $catalog,
        private readonly AvailabilityReader $availability,
        private readonly Returns $returns,
    ) {}

    /**
     * @param  array<string, mixed>  $order  OrderPresenter::present()
     * @return array{lines: list<array<string, mixed>>, reasons: array<string, string>, deadline: ?string}|null null = không còn gì trả được
     */
    public function present(array $order): ?array
    {
        $returnable = array_filter((array) ($order['returnable']['lines'] ?? []), fn (int $quantity): bool => $quantity > 0);
        if ($returnable === []) {
            return null;
        }

        $orderLines = collect((array) $order['lines'])->keyBy('id');
        $lineIds = array_values(array_filter(array_keys($returnable), fn (int $id): bool => $orderLines->has($id)));
        $ownVariants = $this->variants->find(array_values(array_unique(array_map(fn (int $id): int => (int) $orderLines[$id]['variant_id'], $lineIds))));

        $siblingsByStyle = [];
        foreach ($ownVariants as $variant) {
            $siblingsByStyle[$variant->styleCode] ??= array_map(fn ($sibling): int => $sibling->id, $this->variants->ofStyleCode($variant->styleCode));
        }
        $candidateIds = array_values(array_unique(array_merge(...array_values($siblingsByStyle ?: [[]]))));
        $sellable = $candidateIds === [] ? [] : $this->catalog->sellableVariants($candidateIds, app()->getLocale(), now()->getTimestamp());
        $stock = $sellable === [] ? [] : $this->availability->forVariants(array_keys($sellable));

        $lines = [];
        foreach ($lineIds as $lineId) {
            $line = $orderLines[$lineId];
            $own = $ownVariants[(int) $line['variant_id']] ?? null;
            $options = [];
            foreach ($own === null ? [] : $siblingsByStyle[$own->styleCode] as $variantId) {
                $variant = $sellable[$variantId] ?? null;
                if ($variant !== null) {
                    $options[] = [
                        'variant_id' => $variantId,
                        'label' => VariantOptionsText::label($variant->options).($variantId === $own->id ? ' (như cũ)' : ''),
                        'available' => ($stock[$variantId] ?? 0) > 0,
                    ];
                }
            }
            $lines[] = [
                'id' => $lineId, 'name' => $line['name'], 'variant' => (string) $line['variant_label'],
                'image_url' => $line['image_url'], 'returnable' => $returnable[$lineId], 'exchange_options' => $options,
            ];
        }

        // Danh mục lý do do Admin quản lý (§3.13-1), theo ngôn ngữ trang.
        $reasons = array_column($this->returns->reasons(App::getLocale()), 'label', 'code');

        return ['lines' => $lines, 'reasons' => $reasons, 'deadline' => $order['returnable']['deadline'] ?? null];
    }
}
