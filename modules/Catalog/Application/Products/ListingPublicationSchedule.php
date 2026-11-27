<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\PublicationSchedule;
use Modules\Catalog\Domain\ProductStatus;

/**
 * Đọc khung giờ từ read model `product_listing`.
 */
final class ListingPublicationSchedule implements PublicationSchedule
{
    public function nextChangeAfter(int $now): ?int
    {
        $at = Carbon::createFromTimestamp($now);
        $active = fn () => DB::table('product_listing')->where('status', ProductStatus::Active->value);
        $next = array_filter([
            $active()->where('published_from', '>', $at)->min('published_from'),
            $active()->where('published_to', '>', $at)->min('published_to'),
        ]);

        return $next === [] ? null : min(array_map(fn (mixed $value): int => Carbon::parse($value)->getTimestamp(), $next));
    }
}
