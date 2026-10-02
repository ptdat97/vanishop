<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Checkout\Contracts\AddressDirectory;
use Modules\Extension\Contracts\Extensions;

/**
 * Danh mục địa giới cho storefront (API + checkout native) — AddressDirectory đầu tiên đang bật, như Checkout dùng.
 */
final class AddressOptions
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
}
