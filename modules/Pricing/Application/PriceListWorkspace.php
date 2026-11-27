<?php

declare(strict_types=1);

namespace Modules\Pricing\Application;

use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Catalog\Contracts\Data\VariantOptionsText;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Pricing\Contracts\BulkPriceMode;
use Modules\Pricing\Contracts\Data\DiscountCap;
use Modules\Pricing\Contracts\PriceRounding;
use Modules\Pricing\Domain\BulkPriceRule;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\Price;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Support\MoneyFormatter;
use Modules\Tenancy\Contracts\Settings;

/**
 * Trang làm việc của một bảng giá (đợt 1 — thao tác gọn): mọi SKU đang có trong bảng (tìm, phân trang), tìm sản phẩm để
 * thêm, xem trước và áp giá hàng loạt (giảm %, giảm số tiền, đồng giá) tính từ giá niêm yết. Ghi giá luôn qua
 * PriceListService::setPrices (lịch sử giá, audit, PriceChanged). Cảnh báo không chặn: 0đ, không thấp hơn giá niêm yết,
 * giảm vượt mức trần khuyến mãi của cửa hàng.
 */
final class PriceListWorkspace
{
    /** Số dòng xem trước trả về (tóm tắt tính trên mọi dòng). */
    public const PREVIEW_ROWS = 100;

    /**
     * Tối đa SKU mỗi lần xem trước/áp giá hàng loạt (chạy đồng bộ trong request; ghi theo lô — 3.000 SKU ≈ 2 s). Vượt mức →
     * báo lỗi để thu hẹp ô tìm, **không** cắt bớt âm thầm (trước 0.4.69 cắt về 2.000 SKU đầu: bảng 3.000 SKU chỉ đổi giá
     * 2.000 SKU mà vẫn báo thành công). Bằng mức sửa tồn hàng loạt (`StockGrid::MAX_BULK_VARIANTS`).
     */
    public const MAX_BULK = 5000;

    public function __construct(
        private readonly VariantDirectory $variants,
        private readonly PriceListService $lists,
        private readonly MoneyFormatter $money,
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly PriceVariantSearch $search,
    ) {}

    /**
     * Cách tính / làm tròn của plugin (EP v3 X4) cho hộp thoại "Áp giá…".
     *
     * @return array{modes: list<array{code: string, label: string, hint: string}>, roundings: list<array{code: string, label: string}>}
     */
    public function pluginOptions(): array
    {
        return [
            'modes' => array_values(array_map(fn (BulkPriceMode $mode): array => ['code' => $mode->code(), 'label' => $mode->label(), 'hint' => $mode->hint()], $this->modes())),
            'roundings' => array_values(array_map(fn (PriceRounding $rounding): array => ['code' => $rounding->code(), 'label' => $rounding->label()], $this->roundings())),
        ];
    }

    /**
     * @return array<string, BulkPriceMode>
     */
    public function modes(): array
    {
        return array_filter($this->extensions->implementations(BulkPriceMode::TAG, BulkPriceMode::class), fn (BulkPriceMode $mode): bool => ! in_array($mode->code(), BulkPriceRule::MODES, true));
    }

    /**
     * @return array<string, PriceRounding>
     */
    public function roundings(): array
    {
        return $this->extensions->implementations(PriceRounding::TAG, PriceRounding::class);
    }

    /**
     * Trạng thái hiển thị: đang chạy / sắp chạy / đã hết / tắt.
     */
    public static function state(PriceList $list): string
    {
        return match (true) {
            $list->status !== 'active' => 'disabled',
            $list->ends_at !== null && $list->ends_at->isPast() => 'expired',
            $list->starts_at !== null && $list->starts_at->isFuture() => 'scheduled',
            ! $list->isInWindowAt(now()->getTimestamp()) => 'outside_window',
            default => 'running',
        };
    }

    public function capBasisPoints(): int
    {
        return DiscountCap::basisPoints($this->settings->get('core', DiscountCap::SETTING));
    }

    /**
     * SKU trong bảng giá, theo thứ tự tên sản phẩm → mã → trục; `$text` tìm theo SKU, mã, tên sản phẩm.
     *
     * @param  list<int>|null  $only  giới hạn thêm (bộ lọc của plugin — EP v3 X1); null = không giới hạn
     * @return list<int>
     */
    public function variantIds(PriceList $list, string $text = '', ?array $only = null): array
    {
        return $this->search->ordered($this->inList($list, $text, $only))->pluck('i.variant_id')->map(fn ($id): int => (int) $id)->all();
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function rows(PriceList $list, string $text, int $page, int $perPage, string $path, ?array $only = null): LengthAwarePaginator
    {
        $query = $this->inList($list, $text, $only);
        $total = (clone $query)->count();
        $slice = $this->search->ordered($query)->offset(max(0, $page - 1) * $perPage)->limit($perPage)->pluck('i.variant_id')->map(fn ($id): int => (int) $id)->all();

        return new LengthAwarePaginator($this->describe($list, $slice), $total, $perPage, $page, ['path' => $path, 'query' => array_filter(['q' => $text])]);
    }

    /**
     * SKU của bảng giá (mức số lượng 1) khớp chữ tìm — trên read model SKU, không nạp mọi SKU (db-refactor L55).
     *
     * @param  list<int>|null  $only
     */
    private function inList(PriceList $list, string $text, ?array $only): Builder
    {
        return $this->search->matching($text)
            ->join('prices as p', 'p.variant_id', '=', 'i.variant_id')
            ->where('p.price_list_id', $list->id)->where('p.min_qty', 1)
            ->when($only !== null, fn (Builder $query) => $query->whereIn('i.variant_id', array_map('intval', (array) $only)));
    }

    /**
     * Tóm tắt cả bảng giá: số sản phẩm, số SKU, số SKU theo từng cảnh báo.
     *
     * @return array{products: int, variants: int, warnings: array<string, int>}
     */
    public function summary(PriceList $list): array
    {
        $prices = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->pluck('amount', 'variant_id')->map(fn ($amount): int => (int) $amount)->all();
        $base = $this->listAmounts(array_keys($prices), $list);
        $warnings = [];
        foreach ($prices as $variantId => $amount) {
            foreach ($this->rowWarnings($list, $base[$variantId] ?? null, $amount, (int) $variantId) as $warning) {
                $warnings[$warning] = ($warnings[$warning] ?? 0) + 1;
            }
        }
        // Đếm sản phẩm bằng một truy vấn nhẹ của Catalog (không dựng VariantData; không join bảng của Catalog — R11).
        $products = count(array_unique($this->variants->productIdsOf(array_keys($prices))));

        return ['products' => $products, 'variants' => count($prices), 'warnings' => $warnings];
    }

    /**
     * Sản phẩm để thêm vào bảng giá, theo hợp đồng của SkuPicker (`@admin/skuPicker`): tìm theo tên, mã, SKU; tối đa 30 sản
     * phẩm, mỗi sản phẩm kèm mọi SKU. Chữ phụ: khoảng giá niêm yết và số SKU đã có trong bảng; từng SKU: giá niêm yết, giá
     * đang có trong bảng.
     *
     * @param  list<int>|null  $only  SKU thoả bộ lọc phụ của plugin (`variant`); có bộ lọc thì tìm cả khi chưa gõ
     * @return list<array{id: int, code: string, name: string, meta: string|null, variants: list<array{id: int, sku: string, label: string, status: string, meta: string|null}>}>
     */
    public function candidates(PriceList $list, string $text, ?array $only = null): array
    {
        if (trim($text) === '' && $only === null) {
            return [];
        }
        $matching = $this->search->matching($text)->when($only !== null, fn (Builder $query) => $query->whereIn('i.variant_id', array_map('intval', (array) $only)));
        // 30 sản phẩm đầu theo thứ tự hiển thị, mỗi sản phẩm kèm mọi SKU khớp.
        $productIds = (clone $matching)->groupBy('i.name_sort', 'i.product_code', 'i.product_id')
            ->orderBy('i.name_sort')->orderBy('i.product_code')->orderBy('i.product_id')->limit(30)->pluck('i.product_id')->all();
        $byProduct = [];
        foreach ($productIds === [] ? [] : $this->search->ordered((clone $matching)->whereIn('i.product_id', $productIds))->get(['i.product_id', 'i.variant_id']) as $row) {
            $byProduct[(int) $row->product_id][] = (int) $row->variant_id;
        }
        $ids = array_merge([], ...array_values($byProduct));
        $found = $this->variants->find($ids);
        $base = $this->baseAmounts($ids);
        $inList = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $ids)->pluck('amount', 'variant_id')->map(fn ($amount): int => (int) $amount)->all();
        $money = fn (int $amount): string => $this->money->formatAmount($amount, (string) $list->currency_code);

        $result = [];
        foreach ($byProduct as $productId => $variantIds) {
            $first = $found[$variantIds[0]] ?? null;
            $amounts = array_values(array_filter(array_map(fn (int $id): ?int => $base[$id] ?? null, $variantIds), fn (?int $amount): bool => $amount !== null));
            $already = count(array_filter($variantIds, fn (int $id): bool => isset($inList[$id])));
            $meta = array_filter([
                $amounts === [] ? __('pricing::texts.price_list_workspace.no_list_price_yet') : __('pricing::texts.price_list_workspace.list').(min($amounts) === max($amounts) ? $money(min($amounts)) : $money(min($amounts)).' – '.$money(max($amounts))),
                $already > 0 ? "{$already}/".count($variantIds).__('pricing::texts.price_list_workspace.skus_already_list') : null,
            ]);
            $result[] = [
                'id' => (int) $productId, 'code' => (string) $first?->productCode, 'name' => (string) $first?->productName, 'meta' => implode(' · ', $meta),
                'variants' => array_values(array_filter(array_map(function (int $id) use ($found, $base, $inList, $money): ?array {
                    $variant = $found[$id] ?? null;
                    if ($variant === null) {
                        return null;
                    }
                    $meta = array_filter([
                        isset($base[$id]) ? __('pricing::texts.price_list_workspace.list').$money($base[$id]) : null,
                        isset($inList[$id]) ? __('pricing::texts.price_list_workspace.list_2').$money($inList[$id]) : null,
                    ]);

                    return ['id' => $id, 'sku' => $variant->sku, 'label' => VariantOptionsText::label($variant->options), 'status' => $variant->status, 'meta' => $meta === [] ? null : implode(' · ', $meta)];
                }, $variantIds))),
            ];
        }

        return $result;
    }

    /**
     * SKU của các sản phẩm đã chọn (mọi biến thể, cả ngừng bán).
     *
     * @param  list<int>  $productIds
     * @return list<int>
     */
    public function variantIdsOfProducts(array $productIds): array
    {
        $ids = [];
        foreach ($this->variants->ofProducts($productIds) as $variants) {
            foreach ($variants as $variant) {
                $ids[] = $variant->id;
            }
        }

        return $ids;
    }

    /**
     * Xem trước: giá niêm yết → giá mới, % giảm, cảnh báo từng dòng; tóm tắt trên mọi dòng.
     *
     * @param  list<int>  $variantIds
     * @return array{rows: list<array<string, mixed>>, summary: array{total: int, changed: int, skipped: int, warnings: array<string, int>}}
     */
    public function preview(PriceList $list, array $variantIds, BulkPriceRule $rule, ?BulkPriceMode $mode = null, ?PriceRounding $rounding = null, float $value = 0): array
    {
        $computed = $this->compute($list, $variantIds, $rule, $mode, $rounding, $value);
        $warnings = [];
        foreach ($computed as $row) {
            foreach ($row['warnings'] as $warning) {
                $warnings[$warning] = ($warnings[$warning] ?? 0) + 1;
            }
        }
        $found = $this->variants->find(array_slice(array_keys($computed), 0, self::PREVIEW_ROWS));
        $rows = [];
        foreach (array_slice($computed, 0, self::PREVIEW_ROWS, true) as $variantId => $row) {
            $variant = $found[$variantId] ?? null;
            $rows[] = [
                'variant_id' => $variantId, 'name' => (string) $variant?->productName, 'sku' => (string) $variant?->sku,
                'variant_label' => $variant === null ? '' : VariantOptionsText::label($variant->options),
                ...$row,
            ];
        }

        return ['rows' => $rows, 'summary' => [
            'total' => count($computed),
            'changed' => count(array_filter($computed, fn (array $row): bool => $row['new_amount'] !== null && $row['new_amount'] !== $row['current_amount'])),
            'skipped' => count(array_filter($computed, fn (array $row): bool => $row['new_amount'] === null)),
            'warnings' => $warnings,
        ]];
    }

    /**
     * Áp giá: dòng không tính được (chưa có giá niêm yết) bị bỏ qua. Giá gốc (gạch) đang có được giữ khi còn lớn hơn giá mới.
     *
     * @param  list<int>  $variantIds
     * @return array{changed: int, skipped: int, warnings: array<string, int>}
     */
    public function apply(PriceList $list, array $variantIds, BulkPriceRule $rule, ?BulkPriceMode $mode = null, ?PriceRounding $rounding = null, float $value = 0): array
    {
        $computed = $this->compute($list, $variantIds, $rule, $mode, $rounding, $value);
        $compareAt = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', array_keys($computed))->pluck('compare_at_amount', 'variant_id')->all();
        $rows = [];
        $warnings = [];
        foreach ($computed as $variantId => $row) {
            if ($row['new_amount'] === null) {
                continue;
            }
            $compare = isset($compareAt[$variantId]) ? (int) $compareAt[$variantId] : null;
            $rows[] = ['variant_id' => $variantId, 'amount' => $row['new_amount'], 'compare_at_amount' => $compare !== null && $compare > $row['new_amount'] ? $compare : null];
            foreach ($row['warnings'] as $warning) {
                $warnings[$warning] = ($warnings[$warning] ?? 0) + 1;
            }
        }

        return ['changed' => $rows === [] ? 0 : $this->lists->setPrices($list, $rows), 'skipped' => count($computed) - count($rows), 'warnings' => $warnings];
    }

    /**
     * Gỡ SKU khỏi bảng giá (lịch sử giá ghi lại).
     *
     * @param  list<int>  $variantIds
     */
    public function remove(PriceList $list, array $variantIds): int
    {
        $existing = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->pluck('variant_id')->map(fn ($id): int => (int) $id)->all();

        return $existing === [] ? 0 : $this->lists->setPrices($list, array_map(fn (int $id): array => ['variant_id' => $id, 'amount' => null, 'compare_at_amount' => null], $existing));
    }

    /**
     * @param  list<int>  $variantIds
     * @return list<array<string, mixed>>
     */
    private function describe(PriceList $list, array $variantIds): array
    {
        $found = $this->variants->find($variantIds);
        $prices = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->get()->keyBy('variant_id');
        $base = $this->listAmounts($variantIds, $list);
        $rows = [];
        foreach ($variantIds as $id) {
            $variant = $found[$id] ?? null;
            $price = $prices->get($id);
            $amount = $price?->amount === null ? null : (int) $price->amount;
            $rows[] = [
                'variant_id' => $id, 'product_code' => (string) $variant?->productCode, 'name' => (string) $variant?->productName, 'sku' => (string) $variant?->sku,
                'variant_label' => $variant === null ? '' : VariantOptionsText::label($variant->options), 'status' => (string) $variant?->status,
                'list_amount' => $base[$id] ?? null, 'amount' => $amount, 'compare_at_amount' => $price?->compare_at_amount === null ? null : (int) $price->compare_at_amount,
                'percent' => $list->type === PriceListType::Base ? null : BulkPriceRule::discountPercent($base[$id] ?? null, $amount),
                'warnings' => $this->rowWarnings($list, $base[$id] ?? null, $amount, $id),
            ];
        }

        return $rows;
    }

    /**
     * @param  list<int>  $variantIds
     * @return array<int, array{list_amount: int|null, current_amount: int|null, new_amount: int|null, percent: int|null, warnings: list<string>}>
     */
    /**
     * Tính giá mới: cách tính của Core (`BulkPriceRule`) hoặc của plugin (`BulkPriceMode`, EP v3 X4); làm tròn của plugin
     * (`PriceRounding`) thay cho "tới 1.000đ" của Core khi được chọn.
     *
     * @param  list<int>  $variantIds
     * @return array<int, array{list_amount: int|null, current_amount: int|null, new_amount: int|null, percent: int|null, warnings: list<string>}>
     */
    private function compute(PriceList $list, array $variantIds, BulkPriceRule $rule, ?BulkPriceMode $mode = null, ?PriceRounding $rounding = null, float $value = 0): array
    {
        $variantIds = array_values(array_unique($variantIds));
        $base = $this->listAmounts($variantIds, $list, forceBase: true);
        $current = Price::query()->where('price_list_id', $list->id)->where('min_qty', 1)->whereIn('variant_id', $variantIds)->pluck('amount', 'variant_id')->all();
        $fromPlugin = $mode === null ? [] : (array) $this->extensions->call($mode, fn (): array => $mode->compute($variantIds, $value, $base), [], 'pricing.bulk_mode');
        $rows = [];
        foreach ($variantIds as $id) {
            $listAmount = $base[$id] ?? null;
            if ($mode !== null) {
                $raw = $fromPlugin[$id] ?? null;
                $new = is_int($raw) ? max(0, $rounding === null && $rule->roundTo > 0 ? (int) (round($raw / $rule->roundTo) * $rule->roundTo) : $raw) : null;
            } else {
                $new = $rounding === null ? $rule->apply($listAmount) : (new BulkPriceRule($rule->mode, $rule->value, 0))->apply($listAmount);
            }
            if ($new !== null && $rounding !== null) {
                $new = max(0, $rounding->round($new));
            }
            $rows[$id] = [
                'list_amount' => $listAmount, 'current_amount' => isset($current[$id]) ? (int) $current[$id] : null, 'new_amount' => $new,
                'percent' => BulkPriceRule::discountPercent($listAmount, $new),
                'warnings' => $new === null ? [BulkPriceRule::WARN_NO_BASE] : $this->rowWarnings($list, $listAmount, $new, $id),
            ];
        }

        return $rows;
    }

    /**
     * @return list<string>
     */
    private function rowWarnings(PriceList $list, ?int $listAmount, ?int $amount, int $variantId = 0): array
    {
        if ($amount === null || $list->type === PriceListType::Base) {
            $core = $amount === 0 ? [BulkPriceRule::WARN_ZERO] : [];
        } else {
            $core = BulkPriceRule::warnings($listAmount, $amount, $this->capBasisPoints());
        }
        if ($amount === null || $variantId === 0) {
            return $core;
        }
        // Cảnh báo của plugin (EP v3 X4, vd. "Dưới giá vốn"): chỉ được thêm — cảnh báo của Core luôn giữ. Gọi theo từng SKU
        // (cả bảng 3.000 SKU khi tóm tắt) nên bỏ qua hẳn khi không plugin nào nghe.
        $extra = Hook::hasListeners('vani.pricing.bulk.warnings') ? Hook::filter('vani.pricing.bulk.warnings', [], $variantId, $listAmount, $amount, $list->code) : [];

        return array_values(array_unique([...$core, ...array_filter(is_array($extra) ? $extra : [], fn ($warning): bool => is_string($warning) && $warning !== '')]));
    }

    /**
     * Giá niêm yết (bảng `base`, mua từ 1) theo SKU.
     *
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    public function baseAmounts(array $variantIds): array
    {
        $baseId = $variantIds === [] ? null : PriceList::query()->where('code', ListPriceSync::BASE_CODE)->value('id');

        return $baseId === null ? [] : Price::query()->where('price_list_id', $baseId)->where('min_qty', 1)->whereIn('variant_id', $variantIds)
            ->pluck('amount', 'variant_id')->map(fn ($amount): int => (int) $amount)->all();
    }

    /**
     * Giá niêm yết (bảng `base`, mua từ 1) theo SKU. Bảng giá đang xem là `base` thì không so với chính nó, trừ khi cần làm
     * gốc tính giá (`forceBase`).
     *
     * @param  list<int>  $variantIds
     * @return array<int, int>
     */
    private function listAmounts(array $variantIds, PriceList $list, bool $forceBase = false): array
    {
        return $list->type === PriceListType::Base && ! $forceBase ? [] : $this->baseAmounts($variantIds);
    }
}
