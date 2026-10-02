<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Checkout\Contracts\AddressDirectory;
use Modules\Checkout\Contracts\ShippingAddresses;

/**
 * Danh mục địa giới cho storefront (API, checkout và sổ địa chỉ native) — cùng danh mục Checkout dùng.
 */
final class AddressOptions
{
    public function __construct(private readonly ShippingAddresses $addresses) {}

    public function directory(): ?AddressDirectory
    {
        return $this->addresses->directory();
    }
}
