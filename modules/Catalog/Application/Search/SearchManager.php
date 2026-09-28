<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use Modules\Catalog\Contracts\SearchProvider;

/**
 * Chọn SearchProvider theo cấu hình (VANI_SEARCH_PROVIDER) trong các provider đã đăng ký (tag vani.search.providers).
 */
final class SearchManager
{
    public const TAG = 'vani.search.providers';

    public function __construct(
        private readonly Container $container,
        private readonly string $providerCode,
    ) {}

    public function provider(): SearchProvider
    {
        /** @var iterable<SearchProvider> $providers */
        $providers = $this->container->tagged(self::TAG);

        foreach ($providers as $provider) {
            if ($provider->code() === $this->providerCode) {
                return $provider;
            }
        }

        throw new InvalidArgumentException("Search provider [{$this->providerCode}] chưa được đăng ký.");
    }
}
