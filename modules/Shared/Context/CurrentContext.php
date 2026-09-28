<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

use Closure;

/**
 * Phạm vi (actor, channel, brand, locale) của request/job hiện tại.
 * Được đăng ký dạng scoped: mỗi request/job có một instance riêng.
 */
final class CurrentContext
{
    private ?ContextScope $scope = null;

    public function set(ContextScope $scope): void
    {
        $this->scope = $scope;
    }

    public function has(): bool
    {
        return $this->scope !== null;
    }

    public function scope(): ContextScope
    {
        return $this->scope ?? throw new MissingContext;
    }

    public function actor(): Actor
    {
        return $this->scope()->actor;
    }

    public function channelId(): ?int
    {
        return $this->scope()->channelId;
    }

    /**
     * @return list<int>|null null = không giới hạn brand
     */
    public function brandIds(): ?array
    {
        return $this->scope()->brandIds;
    }

    /**
     * Chạy $callback trong một phạm vi khác rồi khôi phục phạm vi cũ.
     *
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public function runAs(ContextScope $scope, Closure $callback): mixed
    {
        $previous = $this->scope;
        $this->scope = $scope;

        try {
            return $callback();
        } finally {
            $this->scope = $previous;
        }
    }

    public function clear(): void
    {
        $this->scope = null;
    }
}
