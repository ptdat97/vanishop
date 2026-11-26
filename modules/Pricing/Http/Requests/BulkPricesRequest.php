<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Contracts\BulkPriceMode;
use Modules\Pricing\Contracts\PriceRounding;
use Modules\Pricing\Domain\BulkPriceRule;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Áp giá hàng loạt / xem trước trên trang làm việc bảng giá. Phạm vi: SKU đã chọn (`variant_ids`), sản phẩm đã chọn để thêm
 * (`product_ids`), hoặc mọi SKU trong bảng khớp ô tìm (`all` + `q`). Cách tính: `mode` + `value` (percent: phần trăm, cho phép
 * một chữ số thập phân), `round_to` (0 | 1000).
 */
final class BulkPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('pricing.manage');
    }

    protected function prepareForValidation(): void
    {
        if ($this->fromProducts()) {
            $this->merge([
                'scope' => 'products',
                'product_ids' => $this->input('ids'),
                'round_to' => $this->input('mode') === 'fixed' ? 0 : $this->input('round_to'),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['variants', 'products', 'all'])],
            'variant_ids' => ['required_if:scope,variants', 'array', 'max:'.PriceListWorkspace::MAX_BULK],
            'variant_ids.*' => ['integer', 'min:1'],
            'product_ids' => ['required_if:scope,products', 'array', $this->fromProducts() ? 'max:2000' : 'max:200'],
            'product_ids.*' => ['integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            // Cách tính của Core + của plugin (BulkPriceMode, EP v3 X4); làm tròn của plugin (PriceRounding) thay "tới 1.000đ".
            'mode' => ['required', Rule::in([...BulkPriceRule::MODES, ...array_keys($this->workspace()->modes())])],
            'value' => ['required', 'numeric', 'min:0', Rule::when($this->input('mode') === 'percent', ['max:100'], ['max:'.PriceListService::MAX_AMOUNT])],
            'round_to' => ['required', 'integer', Rule::in(BulkPriceRule::ROUNDINGS)],
            'rounding' => ['nullable', 'string', Rule::in(array_keys($this->workspace()->roundings()))],
            ...($this->fromProducts() ? [
                'price_list_id' => ['required', 'integer', Rule::exists('price_lists', 'id')->whereNot('type', PriceListType::Base->value)],
            ] : []),
        ];
    }

    private function fromProducts(): bool
    {
        return $this->routeIs('admin.pricing.price-lists.bulk-from-products', 'admin.pricing.price-lists.bulk-from-products.preview');
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['value' => (string) __('pricing::labels.bulk.value'), 'mode' => (string) __('pricing::labels.bulk.mode')];
    }

    public function rule(): BulkPriceRule
    {
        $value = (float) $this->validated('value');
        $mode = (string) $this->validated('mode');
        if (! in_array($mode, BulkPriceRule::MODES, true)) {
            // Cách tính của plugin: Core chỉ giữ phần làm tròn của quy tắc.
            return new BulkPriceRule('fixed', 0, (int) $this->validated('round_to'));
        }

        return new BulkPriceRule($mode, $mode === 'percent' ? (int) round($value * 100) : (int) round($value), (int) $this->validated('round_to'));
    }

    public function pluginMode(): ?BulkPriceMode
    {
        return $this->workspace()->modes()[(string) $this->validated('mode')] ?? null;
    }

    public function pluginRounding(): ?PriceRounding
    {
        $code = $this->validated('rounding');

        return is_string($code) ? ($this->workspace()->roundings()[$code] ?? null) : null;
    }

    public function value(): float
    {
        return (float) $this->validated('value');
    }

    private function workspace(): PriceListWorkspace
    {
        return app(PriceListWorkspace::class);
    }

    /**
     * Mọi SKU trong phạm vi — không cắt bớt: vượt `MAX_BULK` thì báo lỗi kèm số SKU để nhân viên thu hẹp ô tìm.
     *
     * @return list<int>
     *
     * @throws ValidationException
     */
    public function variantIds(PriceList $list, PriceListWorkspace $workspace): array
    {
        $ids = match ($this->validated('scope')) {
            'variants' => array_map('intval', (array) $this->validated('variant_ids')),
            'products' => $workspace->variantIdsOfProducts(array_map('intval', (array) $this->validated('product_ids'))),
            default => $workspace->variantIds($list, trim((string) $this->validated('q'))),
        };
        $ids = array_values(array_unique($ids));
        if (count($ids) > PriceListWorkspace::MAX_BULK) {
            throw ValidationException::withMessages(['scope' => __('pricing::messages.bulk_too_many', ['count' => number_format(count($ids), 0, ',', '.'), 'max' => number_format(PriceListWorkspace::MAX_BULK, 0, ',', '.')])]);
        }

        return $ids;
    }
}
