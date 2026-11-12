<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Checkout\Contracts\ShippingAddresses;
use Modules\Shared\Support\Phones;

/**
 * Hợp đồng đầu vào DUY NHẤT của sổ địa chỉ khách cho mọi kênh (ADR-044 §2): Storefront API `POST/PATCH /me/addresses` và
 * form địa chỉ của Blade. Cửa hàng có danh mục địa giới (vd. `vani.provinces-vn`) → bắt buộc mã tỉnh/phường, tên lấy theo
 * danh mục; không có → bắt buộc tên (nhập tự do). `PATCH` của API là sửa một phần (trường không gửi giữ nguyên); form Blade
 * luôn gửi đủ. Nút "Tải lại phường" của form Blade không JS (`action=reload`) không validate.
 */
final class AddressRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        if ($this->input('action') === 'reload') {
            return [];
        }

        $required = $this->partial() ? 'sometimes' : 'required';
        $directory = $this->container->make(ShippingAddresses::class)->directory() !== null;

        return [
            'label' => ['nullable', 'string', 'max:32'],
            'full_name' => [$required, 'string', 'max:120'],
            'phone' => [$required, 'string', 'max:20', fn (string $attribute, mixed $value, Closure $fail) => Phones::parse((string) $value) === null ? $fail(__('Số điện thoại không hợp lệ.')) : null],
            // Có danh mục: mã bắt buộc, tên lấy theo mã. Không có: tên bắt buộc, mã để trống.
            'province_code' => [$directory ? $required : 'nullable', 'string', 'max:8'],
            'province_name' => [$directory ? 'nullable' : $required, 'string', 'max:64'],
            'ward_code' => [$directory ? $required : 'nullable', 'string', 'max:8'],
            'ward_name' => [$directory ? 'nullable' : $required, 'string', 'max:64'],
            'street_line' => [$required, 'string', 'max:255'],
            'is_default' => ['sometimes', 'boolean'],
        ];
    }

    /** Sửa một phần: `PATCH` của Storefront API. */
    public function partial(): bool
    {
        return $this->isMethod('PATCH');
    }

    /**
     * Dữ liệu cho `CustomerAccounts::addAddress()/updateAddress()`. Sửa một phần chỉ gồm trường đã gửi; còn lại đủ trường
     * (mã rỗng khi nhập tự do, `is_default` theo ô chọn).
     *
     * @return array<string, mixed>
     */
    public function toAddressData(): array
    {
        $data = $this->validated();
        if (array_key_exists('is_default', $data) || ! $this->partial()) {
            $data['is_default'] = $this->boolean('is_default');
        }

        return $this->partial() ? $data : [...$data, 'province_code' => $data['province_code'] ?? '', 'ward_code' => $data['ward_code'] ?? ''];
    }
}
