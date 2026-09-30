<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\InboundHandler;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Connector/InboundHandler có hiệu lực cho một brand (plugin bật ở brand đó). brandId null = cấp Owner.
 */
final class ConnectorRegistry
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @return list<Connector>
     */
    public function connectors(?int $brandId): array
    {
        return array_values($this->extensions->forBrand($brandId, Connector::TAG, Connector::class, fn (Connector $connector): string => $connector->system()));
    }

    public function connector(string $system, ?int $brandId): ?Connector
    {
        return $this->extensions->forBrand($brandId, Connector::TAG, Connector::class, fn (Connector $connector): string => $connector->system())[$system] ?? null;
    }

    public function inboundHandler(string $system, string $messageType): ?InboundHandler
    {
        foreach ($this->extensions->forBrand(null, InboundHandler::TAG, InboundHandler::class, fn (InboundHandler $handler): string => spl_object_hash($handler)) as $handler) {
            if ($handler->system() === $system && $handler->supports($messageType)) {
                return $handler;
            }
        }

        return null;
    }

    /**
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function inBrand(?int $brandId, callable $callback): mixed
    {
        $scope = ContextScope::system('integration');
        if ($brandId !== null) {
            $scope = new ContextScope($scope->actor, brandIds: [$brandId]);
        }

        return $this->context->runAs($scope, $callback);
    }
}
