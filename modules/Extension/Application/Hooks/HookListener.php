<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks;

use Closure;

/**
 * Bọc listener thành object callable: eventy serialize mọi Closure để băm (HashedCallable),
 * điều này lỗi khi closure giữ tham chiếu tới service. Object có __invoke không bị băm.
 */
final readonly class HookListener
{
    public function __construct(
        public ?string $pluginId,
        private Closure $callback,
    ) {}

    public function __invoke(mixed ...$args): mixed
    {
        return ($this->callback)(...$args);
    }
}
