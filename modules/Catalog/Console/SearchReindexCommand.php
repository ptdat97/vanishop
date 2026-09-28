<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Modules\Catalog\Application\Search\MeilisearchSearchProvider;
use Modules\Catalog\Application\Search\ProductDocumentBuilder;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Cấu hình index (nếu provider cần) và index lại toàn bộ sản phẩm.
 */
final class SearchReindexCommand extends Command
{
    protected $signature = 'vani:search:reindex {--setup : Cấu hình index trước (Meilisearch)}';

    protected $description = 'Index lại toàn bộ sản phẩm vào search provider đang cấu hình';

    public function handle(SearchManager $search, ProductDocumentBuilder $documents, CurrentContext $context): int
    {
        $provider = $search->provider();

        if ($this->option('setup') && $provider instanceof MeilisearchSearchProvider) {
            $provider->setup();
            $this->info('Đã cấu hình index Meilisearch.');
        }

        $count = $context->runAs(ContextScope::system('search reindex'), function () use ($provider, $documents): int {
            $count = 0;
            Style::query()->orderBy('id')->chunkById(200, function ($styles) use ($provider, $documents, &$count): void {
                foreach ($styles as $style) {
                    $provider->index($documents->build($style));
                    $count++;
                }
            });

            return $count;
        });

        $this->info("Đã index {$count} sản phẩm bằng provider [{$provider->code()}].");

        return self::SUCCESS;
    }
}
