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
        return array_values(array_filter(
            $this->inBrand($brandId, fn (): array => $this->extensions->tagged(Connector::TAG)),
            fn (object $connector): bool => $connector instanceof Connector,
        ));
    }

    public function connector(string $system, ?int $brandId): ?Connector
    {
        foreach ($this->connectors($brandId) as $connector) {
            if ($connector->system() === $system) {
                return $connector;
            }
        }

        return null;
    }

    public function inboundHandler(string $system, string $messageType): ?InboundHandler
    {
        foreach ($this->inBrand(null, fn (): array => $this->extensions->tagged(InboundHandler::TAG)) as $handler) {
            if ($handler instanceof InboundHandler && $handler->system() === $system && $handler->supports($messageType)) {
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
