<?php

declare(strict_types=1);

namespace Modules\Pricing\Contracts\Data;

use Modules\Pricing\Contracts\ManagedPriceLists;
use Modules\Shared\Contracts\PluginApi;

/**
 * Bảng giá do plugin quản lý ({@see ManagedPriceLists}, 0.5.115): plugin giữ nội dung (vd. chiến
 * dịch flash sale) và tính sẵn giá từng variant; Core giữ bảng giá, lịch, lịch sử giá, giải giá, giá gạch (giá niêm yết), trần
 * giảm, cache trang.
 */
#[PluginApi]
final readonly class ManagedPriceList
{
    public const TYPE_SALE = 'sale';

    public const TYPE_MEMBER = 'member';

    /**
     * @param  string  $type  TYPE_SALE | TYPE_MEMBER (giá trị khác bị từ chối khi sync)
     * @param  array<int, int>  $prices  variant id => giá bán (minor unit, ≥ 0); variant không còn trong mảng bị gỡ khỏi bảng giá
     * @param  int|null  $customerGroupId  bắt buộc với `member`
     * @param  string|null  $editRoute  route Admin của plugin để sửa, nhận một tham số là khoá của plugin
     */
    public function __construct(
        public string $name,
        public string $type,
        public bool $active,
        public array $prices,
        public int $priority = 10,
        public ?int $customerGroupId = null,
        public ?int $startsAt = null,
        public ?int $endsAt = null,
        public ?string $editRoute = null,
    ) {}
}
