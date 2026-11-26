<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Identity\Contracts\StaffNames;
use Modules\Pricing\Application\AdminQueries;
use Modules\Pricing\Application\PriceVariantSearch;
use Modules\Pricing\Persistence\Models\PriceHistory;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Shared\Support\StoreClock;

/**
 * Lịch sử giá theo SKU (stabilization A5): mọi lần đổi giá (tay, hàng loạt, file, nhân bản) — ai, lúc nào, bảng giá nào, giá
 * cũ → mới. Tìm theo SKU, mã hoặc tên sản phẩm.
 */
final class PriceHistoryController
{
    public function index(Request $request, AdminQueries $queries, VariantDirectory $variants, PriceVariantSearch $search, StaffNames $staff, MoneyFormatter $money): Response
    {
        Gate::authorize('pricing.view');
        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'price_list' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', Rule::in([50, 100])],
        ]);
        $text = trim((string) ($data['q'] ?? ''));
        // Truy vấn con trên read model SKU (db-refactor L55) — không nạp danh sách id.
        $variantIds = $text === '' ? null : $search->matching($text)->select('i.variant_id');
        $page = $queries->priceHistory($variantIds, isset($data['price_list']) ? (int) $data['price_list'] : null, (int) ($data['per_page'] ?? 50));

        $rows = $page->getCollection();
        $found = $variants->find($rows->pluck('variant_id')->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $lists = $queries->priceListsById();
        $names = $staff->names($rows->where('actor_type', 'staff')->pluck('actor_id')->filter()->map(fn ($id): int => (int) $id)->unique()->values()->all());
        $format = fn (?int $amount, ?string $currency): ?string => $amount === null ? null : $money->formatAmount($amount, (string) $currency);

        return Inertia::render('Pricing::History/Index', [
            'filters' => ['q' => $search, 'price_list' => $data['price_list'] ?? null],
            'priceLists' => $lists->map(fn (PriceList $list): array => ['id' => $list->id, 'label' => $list->name])->values()->all(),
            'rows' => [
                'data' => $rows->map(function (PriceHistory $row) use ($found, $lists, $names, $format): array {
                    $variant = $found[$row->variant_id] ?? null;
                    $currency = $lists->get($row->price_list_id)?->currency_code;

                    return [
                        'id' => $row->id,
                        'at' => StoreClock::format($row->created_at),
                        'sku' => $variant->sku ?? "#{$row->variant_id}",
                        'product' => $variant === null ? null : trim($variant->productName.' '.VariantOptionsText::label($variant->options)),
                        'price_list' => $lists->get($row->price_list_id)?->name,
                        'old' => $format($row->old_amount, $currency),
                        'new' => $format($row->new_amount, $currency),
                        'old_compare' => $format($row->old_compare_at_amount, $currency),
                        'new_compare' => $format($row->new_compare_at_amount, $currency),
                        'actor' => match ($row->actor_type) {
                            'staff' => $names[(int) $row->actor_id] ?? __('pricing::labels.history.staff_removed'),
                            null => __('pricing::labels.history.system'),
                            default => (string) $row->actor_type,
                        },
                    ];
                })->all(),
                'current' => $page->currentPage(), 'last' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(),
                'total' => $page->total(), 'per_page' => $page->perPage(),
            ],
            'urls' => ['base' => route('admin.pricing.history'), 'priceLists' => route('admin.pricing.price-lists.index')],
        ]);
    }
}
