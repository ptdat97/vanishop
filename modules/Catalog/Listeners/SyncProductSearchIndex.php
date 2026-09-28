<?php

declare(strict_types=1);

namespace Modules\Catalog\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Catalog\Application\Search\ProductDocumentBuilder;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Events\ProductArchived;
use Modules\Catalog\Events\ProductCreated;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Đồng bộ chỉ mục tìm kiếm sau khi sản phẩm thay đổi. Idempotent: luôn đọc trạng thái mới nhất từ DB.
 */
final class SyncProductSearchIndex implements ShouldQueue
{
    public string $queue = 'search';

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public function __construct(
        private readonly SearchManager $search,
        private readonly ProductDocumentBuilder $documents,
        private readonly CurrentContext $context,
    ) {}

    public function handle(ProductCreated|ProductUpdated|ProductArchived $event): void
    {
        $this->context->runAs(ContextScope::system('search indexing'), function () use ($event): void {
            $provider = $this->search->provider();
            $style = Style::query()->find($event->styleId);

            if ($style === null) {
                $provider->remove($event->styleId);

                return;
            }

            $provider->index($this->documents->build($style));
        });
    }
}
