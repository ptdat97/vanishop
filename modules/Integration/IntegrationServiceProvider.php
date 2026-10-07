<?php

declare(strict_types=1);

namespace Modules\Integration;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Modules\Extension\Application\Admin\AdminNavigation;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Application\PermissionRegistry;
use Modules\Integration\Application\ConnectorRegistry;
use Modules\Integration\Application\DatabaseReferences;
use Modules\Integration\Application\Delivery\CircuitBreaker;
use Modules\Integration\Application\Delivery\MessageRouter;
use Modules\Integration\Application\Delivery\WebhookSender;
use Modules\Integration\Application\EventPublisher;
use Modules\Integration\Application\InboxProcessor;
use Modules\Integration\Application\InboxService;
use Modules\Integration\Application\Listeners\PublishDomainEvents;
use Modules\Integration\Application\OutboxWorker;
use Modules\Integration\Console\ClientCommand;
use Modules\Integration\Console\DispatchOutboxCommand;
use Modules\Integration\Console\ProcessInboxCommand;
use Modules\Integration\Console\ReconcileOrdersCommand;
use Modules\Integration\Console\ReplayCommand;
use Modules\Integration\Console\WebhookCommand;
use Modules\Integration\Contracts\Connector;
use Modules\Integration\Contracts\ExternalReferences;
use Modules\Integration\Contracts\Inbox;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Integration\Contracts\Mappings;
use Modules\Integration\Domain\RetryPolicy;
use Modules\Integration\Http\Middleware\AuthenticateIntegrationClient;
use Modules\Integration\Http\Middleware\RequireIntegrationScope;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Shared\Contracts\Metrics;
use Modules\Shared\Support\ModuleServiceProvider;

/**
 * Integration platform: event feed, outbox/inbox, webhook, Integration API v1, replay (ADR-005, ADR-013).
 * Không chứa connector cụ thể — connector là plugin (`Connector::TAG`, `InboundHandler::TAG`).
 */
final class IntegrationServiceProvider extends ModuleServiceProvider
{
    protected function moduleName(): string
    {
        return 'Integration';
    }

    public function register(): void
    {
        // Đăng ký ở register() (trước mọi boot()) để listener này chạy TRƯỚC listener của module khác:
        // vd. Payment tự xác nhận đơn COD ngay trong lúc dispatch OrderPlaced — nếu chạy sau, feed sẽ có
        // order.confirmed trước order.created.
        foreach (PublishDomainEvents::listeners() as $event => $method) {
            Event::listen($event, [PublishDomainEvents::class, $method]);
        }

        $this->app->bind(IntegrationEvents::class, EventPublisher::class);
        $this->app->bind(Inbox::class, InboxService::class);
        $this->app->bind(ExternalReferences::class, DatabaseReferences::class);
        $this->app->bind(Mappings::class, DatabaseReferences::class);
        $this->app->bind(ConnectorRegistry::class);

        $this->app->bind(RetryPolicy::class, fn (): RetryPolicy => new RetryPolicy(
            array_values(array_map('intval', (array) config('vanishop.integration.retry_delays', [60, 300, 900, 3600, 21600, 86400]))),
            (float) config('vanishop.integration.retry_jitter', 0.2),
        ));
        $this->app->bind(CircuitBreaker::class, fn ($app): CircuitBreaker => new CircuitBreaker(
            $app->make('cache')->store(),
            (int) config('vanishop.integration.circuit_threshold', 5),
            (int) config('vanishop.integration.circuit_cooldown', 60),
        ));
        $this->app->bind(WebhookSender::class, fn (): WebhookSender => new WebhookSender((int) config('vanishop.integration.webhook_pause_after_hours', 24)));
        $this->app->bind(OutboxWorker::class, fn ($app): OutboxWorker => new OutboxWorker(
            $app->make(MessageRouter::class), $app->make(RetryPolicy::class), $app->make(Metrics::class), (int) config('vanishop.integration.processing_timeout', 600),
        ));
        $this->app->bind(InboxProcessor::class, fn ($app): InboxProcessor => new InboxProcessor(
            $app->make(ConnectorRegistry::class), $app->make(RetryPolicy::class), (int) config('vanishop.integration.processing_timeout', 600),
        ));
    }

    public function boot(PermissionRegistry $permissions, AdminNavigation $navigation, Router $router): void
    {
        $this->app->make(Extensions::class)->kindContract('integration', Connector::TAG);
        $permissions->register('integration.view', 'Xem tình trạng tích hợp (outbox/inbox, client)');
        $permissions->register('integration.replay', 'Gửi lại message tích hợp lỗi');
        $permissions->register('integration.manage', 'Quản lý Integration Client và webhook');

        $navigation->add('integration', 'Tích hợp', 'admin.integration.health', 'integration.view', 800);

        $router->aliasMiddleware('vani.integration-client', AuthenticateIntegrationClient::class);
        $router->aliasMiddleware('vani.integration-scope', RequireIntegrationScope::class);

        RateLimiter::for('integration-api', function (Request $request): Limit {
            $client = $request->attributes->get(AuthenticateIntegrationClient::ATTRIBUTE);

            return $client instanceof IntegrationClient
                ? Limit::perMinute(max(1, $client->rate_limit))->by('integration-client:'.$client->id)
                : Limit::perMinute(60)->by('integration-ip:'.$request->ip());
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            // Dự phòng khi chưa chạy worker liên tục (`--work` dưới supervisor/Horizon).
            $schedule->command('vani:integration:dispatch')->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:integration:process-inbox')->everyMinute()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:integration:reconcile-orders')->hourly()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([DispatchOutboxCommand::class, ProcessInboxCommand::class, ReplayCommand::class, ReconcileOrdersCommand::class, ClientCommand::class, WebhookCommand::class]);
        }

        if (! $this->app->routesAreCached()) {
            Route::middleware(['api', 'vani.integration-client', 'throttle:integration-api'])
                ->prefix('api/integration/v1')
                ->name('api.integration.v1.')
                ->group($this->modulePath('Http/routes/integration-api.php'));
        }

        $this->loadAdminRoutes($this->modulePath('Http/routes/admin.php'));
        $this->bootModuleResources();
    }
}
