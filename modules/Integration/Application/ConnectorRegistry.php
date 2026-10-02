<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Modules\Extension\Contracts\Extensions;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\InboundHandler;

/**
 * Connector/InboundHandler đang có hiệu lực (plugin đang bật).
 */
final class ConnectorRegistry
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return list<Connector>
     */
    public function connectors(): array
    {
        return array_values($this->extensions->implementations(Connector::TAG, Connector::class, fn (Connector $connector): string => $connector->system()));
    }

    public function connector(string $system): ?Connector
    {
        return $this->extensions->implementations(Connector::TAG, Connector::class, fn (Connector $connector): string => $connector->system())[$system] ?? null;
    }

    public function inboundHandler(string $system, string $messageType): ?InboundHandler
    {
        foreach ($this->extensions->implementations(InboundHandler::TAG, InboundHandler::class, fn (InboundHandler $handler): string => spl_object_hash($handler)) as $handler) {
            if ($handler->system() === $system && $handler->supports($messageType)) {
                return $handler;
            }
        }

        return null;
    }
}
