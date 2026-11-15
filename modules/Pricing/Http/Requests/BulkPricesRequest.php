<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Pricing\Application\PriceListService;
use Modules\Pricing\Application\PriceListWorkspace;
use Modules\Pricing\Domain\BulkPriceRule;
use Modules\Pricing\Persistence\Models\PriceList;

/**
 * Áp giá hàng loạt / xem trước trên trang làm việc bảng giá. Phạm vi: SKU đã chọn (`variant_ids`), sản phẩm đã chọn để thêm
 * (`style_ids`), hoặc mọi SKU trong bảng khớp ô tìm (`all` + `q`). Cách tính: `mode` + `value` (percent: phần trăm, cho phép
 * một chữ số thập phân), `round_to` (0 | 1000).
 */
final class BulkPricesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('pricing.manage');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::in(['variants', 'styles', 'all'])],
            'variant_ids' => ['required_if:scope,variants', 'array', 'max:'.PriceListWorkspace::MAX_BULK],
            'variant_ids.*' => ['integer', 'min:1'],
            'style_ids' => ['required_if:scope,styles', 'array', 'max:200'],
            'style_ids.*' => ['integer', 'min:1'],
            'q' => ['nullable', 'string', 'max:100'],
            'mode' => ['required', Rule::in(BulkPriceRule::MODES)],
            'value' => ['required', 'numeric', 'min:0', Rule::when($this->input('mode') === 'percent', ['max:100'], ['max:'.PriceListService::MAX_AMOUNT])],
            'round_to' => ['required', 'integer', Rule::in(BulkPriceRule::ROUNDINGS)],
        ];
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

        return new BulkPriceRule($mode, $mode === 'percent' ? (int) round($value * 100) : (int) round($value), (int) $this->validated('round_to'));
    }

    /**
     * @return list<int>
     */
    public function variantIds(PriceList $list, PriceListWorkspace $workspace): array
    {
        $ids = match ($this->validated('scope')) {
            'variants' => array_map('intval', (array) $this->validated('variant_ids')),
            'styles' => $workspace->variantIdsOfStyles(array_map('intval', (array) $this->validated('style_ids'))),
            default => $workspace->variantIds($list, trim((string) $this->validated('q'))),
        };

        return array_slice(array_values(array_unique($ids)), 0, PriceListWorkspace::MAX_BULK);
    }
}
