<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Modules\Storefront\Application\AddressOptions;

/**
 * Danh mục địa giới cho form địa chỉ (AddressDirectory của plugin, vd. vani.provinces-vn). Không có danh mục →
 * `data: []`, `meta.directory: null` (client cho nhập tự do).
 */
final class AddressController
{
    public function provinces(AddressOptions $addresses): JsonResponse
    {
        $directory = $addresses->directory();

        return response()->json(['data' => $directory?->provinces() ?? [], 'meta' => ['directory' => $directory?->code()]])
            ->setPublic()->setMaxAge(3600);
    }

    public function wards(string $province, AddressOptions $addresses): JsonResponse
    {
        $directory = $addresses->directory();
        abort_if($directory === null || $directory->province($province) === null, 404);

        return response()->json(['data' => $directory->wards($province), 'meta' => ['directory' => $directory->code()]])
            ->setPublic()->setMaxAge(3600);
    }
}
