<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts;

use DateTimeInterface;
use Illuminate\Http\Request;
use Modules\Shared\Contracts\PluginApi;

/** Mốc thay đổi sớm nhất của nội dung HTML trong request hiện tại. */
#[PluginApi]
final class PublicPageExpiry
{
    private const ATTRIBUTE = 'vani.public_page_expires_at';

    public static function limit(Request $request, DateTimeInterface $at): void
    {
        $request->attributes->set(self::ATTRIBUTE, min($at->getTimestamp(), self::timestamp($request) ?? PHP_INT_MAX));
    }

    public static function timestamp(Request $request): ?int
    {
        return $request->attributes->get(self::ATTRIBUTE);
    }
}
