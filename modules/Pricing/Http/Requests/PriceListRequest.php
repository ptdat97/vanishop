<?php

declare(strict_types=1);

namespace Modules\Pricing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Pricing\Application\ListPriceSync;
use Modules\Pricing\Contracts\CustomerGroupDirectory;
use Modules\Pricing\Domain\PriceListType;
use Modules\Pricing\Persistence\Models\PriceList;
use Modules\Shared\Domain\Schedule\WeeklySchedule;
use Modules\Shared\Http\Rules\WeeklyScheduleRule;
use Modules\Shared\Support\StoreClock;

final class PriceListRequest extends FormRequest
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
        $list = $this->route('priceList');
        $list = $list instanceof PriceList ? $list : null;
        $isMainBase = $list?->code === ListPriceSync::BASE_CODE;
        // Một bảng giá niêm yết duy nhất (mã `base`): tạo mới chỉ Sale/Thành viên; bảng niêm yết chính giữ loại và mã.
        // Chưa có bảng niêm yết nào (cài mới) thì được tạo, mã luôn là `base`.
        $baseAllowed = $isMainBase || ($list === null && ! PriceList::query()->where('type', PriceListType::Base)->exists());

        return [
            'code' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_-]*$/',
                Rule::unique('price_lists', 'code')->ignore($list?->id),
                Rule::prohibitedIf($isMainBase && $this->input('code') !== null && $this->input('code') !== ListPriceSync::BASE_CODE)],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in($isMainBase ? [PriceListType::Base->value] : array_values(array_filter(
                array_column(PriceListType::cases(), 'value'),
                fn (string $type): bool => $type !== PriceListType::Base->value || $baseAllowed,
            )))],
            // Giá thành viên: bảng `member` bắt buộc gắn nhóm khách; loại khác có thể gắn (vd. sale riêng cho VIP).
            'customer_group_id' => [Rule::requiredIf($this->input('type') === PriceListType::Member->value), 'nullable', 'integer',
                Rule::in(array_column(app(CustomerGroupDirectory::class)->groups(), 'id'))],
            'priority' => ['nullable', 'integer', 'min:-1000', 'max:1000'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
            'recurrence' => ['nullable', 'array', new WeeklyScheduleRule],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'lock_version' => [$list instanceof PriceList ? 'required' : 'nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array{code: string, name: string, type: string, customer_group_id: ?int, priority: int, starts_at: ?string, ends_at: ?string, recurrence: list<array{days: list<int>, from: string, to: string}>|null, status: string}
     */
    public function toData(): array
    {
        return [
            'code' => $this->validated('type') === PriceListType::Base->value ? ListPriceSync::BASE_CODE : (string) ($this->validated('code') ?? ''),
            'name' => (string) $this->validated('name'),
            'type' => (string) $this->validated('type'),
            'customer_group_id' => $this->validated('customer_group_id') === null ? null : (int) $this->validated('customer_group_id'),
            // Để trống = mặc định theo loại (niêm yết 0, sale 10, thành viên 20) — nhân viên không cần biết "priority".
            'priority' => $this->validated('priority') === null ? PriceListType::from((string) $this->validated('type'))->defaultPriority() : (int) $this->validated('priority'),
            // Nhân viên nhập theo giờ cửa hàng; lưu UTC.
            'starts_at' => $this->validated('starts_at') === null ? null : StoreClock::fromInput((string) $this->validated('starts_at'))->toDateTimeString(),
            'ends_at' => $this->validated('ends_at') === null ? null : StoreClock::fromInput((string) $this->validated('ends_at'))->toDateTimeString(),
            // Lịch lặp theo tuần (0.5.116); bảng giá niêm yết luôn chạy.
            'recurrence' => $this->validated('type') === PriceListType::Base->value ? null : WeeklySchedule::fromArray($this->validated('recurrence'))?->toArray(),
            'status' => (string) $this->validated('status'),
        ];
    }
}
