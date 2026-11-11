<?php

declare(strict_types=1);

namespace Modules\Pricing\Application\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Pricing\Application\ListPriceSync;
use Modules\Pricing\Events\PriceChanged;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Giá đổi → cập nhật giá niêm yết thấp nhất của các sản phẩm liên quan sang Catalog (lọc/sắp xếp theo giá). Idempotent:
 * luôn tính lại từ bảng giá `base` hiện tại, nên chạy cho mọi bảng giá (kể cả bảng `base` vừa bị xoá) đều đúng.
 */
final class SyncListPrices implements ShouldQueue
{
    public string $queue = 'search';

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly ListPriceSync $sync,
        private readonly CurrentContext $context,
    ) {}

    public function handle(PriceChanged $event): void
    {
        $this->context->runAs(ContextScope::system('list price sync'), fn () => $this->sync->forVariants($event->variantIds));
    }
}
