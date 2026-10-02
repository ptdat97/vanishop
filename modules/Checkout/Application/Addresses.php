<?php

declare(strict_types=1);

namespace Modules\Checkout\Application;

use Modules\Checkout\Contracts\AddressDirectory;
use Modules\Extension\Contracts\Extensions;

/**
 * Danh mục địa giới đang dùng (AddressDirectory đầu tiên đang bật) + chuẩn hoá địa chỉ giao hàng theo danh mục.
 */
final class Addresses
{
    public function __construct(private readonly Extensions $extensions) {}

    public function directory(): ?AddressDirectory
    {
        foreach ($this->extensions->tagged(AddressDirectory::TAG) as $directory) {
            if ($directory instanceof AddressDirectory) {
                return $directory;
            }
        }

        return null;
    }

    /**
     * Có danh mục: mã tỉnh/phường phải hợp lệ và khớp nhau → tên lấy theo danh mục. Trả null nếu không hợp lệ;
     * không có danh mục → trả nguyên địa chỉ.
     *
     * @param  array<string, string>  $address
     * @return array<string, string>|null
     */
    public function normalize(array $address): ?array
    {
        $directory = $this->directory();
        if ($directory === null) {
            return $address;
        }

        $province = $directory->province((string) ($address['province_code'] ?? ''));
        $ward = $province === null ? null : $directory->ward($province['code'], (string) ($address['ward_code'] ?? ''));
        if ($province === null || $ward === null) {
            return null;
        }

        return [...$address, 'province_code' => $province['code'], 'province_name' => $province['name'], 'ward_code' => $ward['code'], 'ward_name' => $ward['name']];
    }
}
