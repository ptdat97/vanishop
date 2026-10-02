<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\Log;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Throwable;

/**
 * Domain event → listener của plugin: chỉ gọi khi plugin đang bật, chạy với actor hệ thống của plugin.
 *
 * Domain event là phản ứng phụ sau commit: lỗi của listener plugin được ghi log, không làm hỏng request/job.
 */
final class PluginEventListeners
{
    public function __construct(
        private readonly Container $container,
        private readonly Dispatcher $events,
        private readonly CurrentContext $context,
    ) {}

    /**
     * @param  class-string  $event
     * @param  callable|class-string|array{0: class-string, 1: string}  $handler
     */
    public function listen(string $event, callable|string|array $handler, string $pluginId): void
    {
        $this->events->listen($event, function (object $payload) use ($handler, $pluginId): void {
            $this->dispatch($payload, $handler, $pluginId);
        });
    }

    /**
     * @param  callable|class-string|array{0: class-string, 1: string}  $handler
     */
    private function dispatch(object $payload, callable|string|array $handler, string $pluginId): void
    {
        $this->context->runAs(new ContextScope(Actor::system("plugin {$pluginId}")), function () use ($payload, $handler, $pluginId): void {
            if (! $this->container->make(PluginActivation::class)->isActive($pluginId)) {
                return;
            }

            try {
                $this->resolve($handler)($payload);
            } catch (Throwable $exception) {
                report($exception);
                Log::error('Listener của plugin lỗi khi xử lý domain event.', [
                    'plugin' => $pluginId, 'event' => $payload::class, 'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    /**
     * @param  callable|class-string|array{0: class-string, 1: string}  $handler
     */
    private function resolve(callable|string|array $handler): Closure
    {
        if (is_string($handler) && class_exists($handler)) {
            return fn (object $payload): mixed => $this->container->make($handler)->handle($payload);
        }
        if (is_array($handler) && is_string($handler[0]) && class_exists($handler[0])) {
            return fn (object $payload): mixed => $this->container->make($handler[0])->{$handler[1]}($payload);
        }

        return Closure::fromCallable($handler);
    }
}
