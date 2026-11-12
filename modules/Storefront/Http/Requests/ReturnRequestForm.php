<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Modules\Returns\Contracts\Returns;

/**
 * Hợp đồng đầu vào DUY NHẤT của yêu cầu đổi/trả cho mọi kênh (ADR-044 §2): Storefront API `POST /orders/{id}/returns` và
 * form đổi trả của Blade. Dạng chuẩn là của API — `lines[]{order_line_id, quantity, exchange_variant_id?}`, `resolution`
 * tuỳ chọn. Form Blade gửi `lines[<id dòng đơn>][quantity|exchange_variant_id]` (ô số lượng cho mọi dòng, 0 = không trả)
 * và được đổi sang dạng chuẩn trước khi validate, nên hai kênh cùng luật:
 *
 * - phải chọn ít nhất một dòng có số lượng > 0;
 * - đổi hàng (`resolution=exchange`, hoặc có dòng mang `exchange_variant_id`) thì MỌI dòng phải chọn hàng đổi —
 *   một yêu cầu không trộn dòng đổi với dòng hoàn tiền.
 */
final class ReturnRequestForm extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $lines = $this->input('lines');
        // Form Blade: khoá là id dòng đơn → danh sách dạng API, bỏ dòng số lượng 0.
        if (is_array($lines) && $lines !== [] && ! array_is_list($lines)) {
            $exchange = $this->input('resolution') === 'exchange';
            $converted = [];
            foreach ($lines as $lineId => $line) {
                $quantity = (int) (is_array($line) ? ($line['quantity'] ?? 0) : 0);
                if ($quantity > 0) {
                    $converted[] = ['order_line_id' => (int) $lineId, 'quantity' => $quantity, 'exchange_variant_id' => $exchange ? (((int) ($line['exchange_variant_id'] ?? 0)) ?: null) : null];
                }
            }
            $this->merge(['lines' => $converted]);
        }

        if (! $this->filled('resolution')) {
            $exchange = collect((array) $this->input('lines'))->contains(fn (mixed $line): bool => is_array($line) && ! empty($line['exchange_variant_id']));
            $this->merge(['resolution' => $exchange ? 'exchange' : 'refund']);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'resolution' => ['required', Rule::in(['refund', 'exchange'])],
            'lines' => ['required', 'array', 'min:1', 'max:50'],
            'lines.*.order_line_id' => ['required', 'integer'],
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'lines.*.exchange_variant_id' => ['nullable', 'integer'],
            // Mã lý do trong danh mục đang bật (Admin → Đổi trả → Lý do).
            'reason_code' => ['required', 'string', Rule::in(array_column($this->container->make(Returns::class)->reasons(App::getLocale()), 'code'))],
            'note' => ['nullable', 'string', 'max:500'],
            // Ảnh đính kèm, kiểm trước khi tạo yêu cầu để không tạo yêu cầu rồi mới báo ảnh lỗi.
            'photos' => ['nullable', 'array', 'max:'.Returns::MAX_ATTACHMENTS],
            'photos.*' => ['file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lines.required' => __('storefront::messages.return_nothing_selected'),
            'lines.min' => __('storefront::messages.return_nothing_selected'),
        ];
    }

    /**
     * @return list<callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('resolution') !== 'exchange' || $validator->errors()->has('lines')) {
                return;
            }
            foreach ((array) $this->input('lines') as $line) {
                if (empty($line['exchange_variant_id'])) {
                    $validator->errors()->add('lines', __('storefront::messages.return_exchange_missing'));

                    return;
                }
            }
        }];
    }

    /**
     * Đầu vào cho `Returns::request()`: số lượng theo dòng đơn (dòng lặp được cộng dồn), hàng đổi theo dòng.
     *
     * @return array{lines: array<int, int>, exchanges: array<int, int>, reason_code: string, note: string|null, photos: list<mixed>}
     */
    public function toReturnInput(): array
    {
        $lines = [];
        $exchanges = [];
        foreach ((array) $this->validated('lines') as $line) {
            $lineId = (int) $line['order_line_id'];
            $lines[$lineId] = ($lines[$lineId] ?? 0) + (int) $line['quantity'];
            if ($this->validated('resolution') === 'exchange') {
                $exchanges[$lineId] = (int) $line['exchange_variant_id'];
            }
        }

        return [
            'lines' => $lines,
            'exchanges' => $exchanges,
            'reason_code' => (string) $this->validated('reason_code'),
            'note' => $this->validated('note'),
            'photos' => array_values((array) $this->validated('photos', [])),
        ];
    }
}
