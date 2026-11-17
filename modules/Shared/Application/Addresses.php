<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Closure;
use Modules\Shared\Contracts\AddressDirectory;
use Modules\Shared\Contracts\ShippingAddresses;

/**
 * Danh mục địa giới đang dùng (AddressDirectory đầu tiên đang bật) + chuẩn hoá địa chỉ giao hàng theo danh mục.
 * Danh sách danh mục do composition root cung cấp (Shared không phụ thuộc registry của Extension).
 */
final class Addresses implements ShippingAddresses
{
    /**
     * @param  Closure(): iterable<mixed>  $directories
     */
    public function __construct(private readonly Closure $directories) {}

    public function directory(): ?AddressDirectory
    {
        foreach (($this->directories)() as $directory) {
            if ($directory instanceof AddressDirectory) {
                return $directory;
            }
        }

        return null;
    }

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
