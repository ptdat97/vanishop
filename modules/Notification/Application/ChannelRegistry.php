<?php

declare(strict_types=1);

namespace Modules\Notification\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Notification\Contracts\NotificationChannel;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Kênh có hiệu lực cho một brand (plugin kênh bật ở brand đó). brandId null = cấp Owner.
 */
final class ChannelRegistry
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return array<string, NotificationChannel> code => channel
     */
    public function forBrand(?int $brandId): array
    {
        return $this->extensions->forBrand($brandId, NotificationChannel::TAG, NotificationChannel::class);
    }

    /**
     * Chạy lời gọi kênh trong phạm vi brand (plugin đọc cấu hình theo brand).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function inBrand(?int $brandId, callable $callback): mixed
    {
        $scope = ContextScope::system('notification send');
        if ($brandId !== null) {
            $scope = new ContextScope($scope->actor, brandIds: [$brandId]);
        }

        return $this->context->runAs($scope, $callback);
    }
}
