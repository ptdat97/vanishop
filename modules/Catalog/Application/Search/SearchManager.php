<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use InvalidArgumentException;
use Modules\Catalog\Contracts\SearchProvider;
use Modules\Extension\Contracts\Extensions;

/**
 * Chọn SearchProvider theo cấu hình (VANI_SEARCH_PROVIDER) trong các provider có hiệu lực trong phạm vi hiện tại
 * (SearchProvider do plugin cung cấp chỉ có mặt khi plugin được bật).
 */
final class SearchManager
{
    public const TAG = 'vani.search.providers';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly string $providerCode,
    ) {}

    public function provider(): SearchProvider
    {
        foreach ($this->extensions->tagged(self::TAG) as $provider) {
            if ($provider instanceof SearchProvider && $provider->code() === $this->providerCode) {
                return $provider;
            }
        }

        throw new InvalidArgumentException("Search provider [{$this->providerCode}] chưa được đăng ký.");
    }
}
