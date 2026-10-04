<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Tạo phiếu chuyển kho: dòng nhập theo SKU (resolve sang variant ở controller).
 */
final class StockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('inventory.transfer');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'from_location_id' => ['required', 'integer'],
            'to_location_id' => ['required', 'integer', 'different:from_location_id'],
            'note' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:128'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sku' => ['required', 'string', 'max:64'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ];
    }
}
