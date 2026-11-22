<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

/** Các nhóm middleware do module sở hữu Storefront cung cấp cho nơi đăng ký route. */
final readonly class StorefrontRouteMiddleware
{
    /**
     * @param  list<string>  $api
     * @param  list<string>  $page
     * @param  list<string>  $cacheable
     * @param  list<string>  $account
     * @param  list<class-string|string>  $sessionless
     */
    public function __construct(
        public array $api,
        public array $page,
        public array $cacheable,
        public array $account,
        public array $sessionless,
    ) {}

    /**
     * @param  list<string>  $middleware
     * @return list<string>
     */
    public function localized(array $middleware, string $locale): array
    {
        return array_map(fn (string $entry): string => str_replace('{locale}', $locale, $entry), $middleware);
    }
}
