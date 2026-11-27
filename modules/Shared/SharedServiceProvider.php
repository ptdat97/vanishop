<?php

declare(strict_types=1);

namespace Modules\Shared;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Factory as ValidationFactory;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Application\PageCache\RequestPageTags;
use Modules\Shared\Console\InvalidatePageCacheCommand;
use Modules\Shared\Console\PruneIdempotencyKeysCommand;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Contracts\PageCacheInvalidator;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Shared\Http\LocalizedValidator;
use Modules\Shared\Http\Middleware\FirstPartySession;
use Modules\Shared\Http\Middleware\ResolveStorefrontContext;
use Modules\Shared\Http\RateLimits;
use Modules\Shared\Support\ExternalCalls;
use Modules\Shared\Support\ModuleServiceProvider;
use Modules\Shared\Support\MoneyFormatter;
use Psr\Http\Message\RequestInterface;
use Throwable;

final class SharedServiceProvider extends ModuleServiceProvider
{
    /** Lệnh deploy/bảo trì làm cache trang hết hiệu lực (ADR-051). */
    public const PAGE_CACHE_FLUSHING_COMMANDS = ['optimize', 'optimize:clear', 'view:cache', 'view:clear', 'config:cache', 'config:clear', 'cache:clear'];

    protected function moduleName(): string
    {
        return 'Shared';
    }

    public function register(): void
    {
        $this->app->scoped(CurrentContext::class);
        // Cache trang (ADR-051): bộ gom tag theo request, hàng đợi xoá dùng chung.
        $this->app->scoped(RequestPageTags::class);
        $this->app->alias(RequestPageTags::class, PageCacheTags::class);
        $this->app->singleton(BatchedPageCacheInvalidator::class);
        $this->app->alias(BatchedPageCacheInvalidator::class, PageCacheInvalidator::class);
        $this->app->singleton(MoneyFormatter::class, fn (): MoneyFormatter => MoneyFormatter::fromConfig());
    }

    public function boot(Router $router): void
    {
        $router->aliasMiddleware('vani.storefront-context', ResolveStorefrontContext::class);
        $router->aliasMiddleware('vani.first-party-session', FirstPartySession::class);
        // Tên trường dễ đọc cho khoá lồng nhau (translations.vi.name, cells.0.sku) trong thông báo validation.
        $this->app->make(ValidationFactory::class)->resolver(
            fn ($translator, array $data, array $rules, array $messages, array $attributes): LocalizedValidator => new LocalizedValidator($translator, $data, $rules, $messages, $attributes),
        );
        // Mọi request HTTP ra ngoài (plugin, connector, cổng…) đi qua ranh giới transaction: gọi mạng trong transaction chưa
        // commit (listener hook `transaction: true`, calculator, validator) bị chặn ở dev/test, cảnh báo ở production.
        Http::globalRequestMiddleware(function (RequestInterface $request): RequestInterface {
            ExternalCalls::guard('http:'.$request->getUri()->getHost());

            return $request;
        });
        RateLimiter::for('storefront-api', fn (Request $request): Limit => RateLimits::perMinuteByIp($request, (int) config('vanishop.rate_limits.storefront_api', 240)));

        // Tag cache trang đã gom trong bộ nhớ → hàng đợi, một lần khi request/lệnh/job kết thúc (ADR-051).
        $flushPageCacheTags = fn () => $this->app->make(BatchedPageCacheInvalidator::class)->flushBuffer();
        // Request web xong (trước khi gửi response): thao tác nhỏ của nhân viên xoá ngay, còn lại vào hàng đợi.
        Event::listen(RequestHandled::class, function (): void {
            $context = $this->app->make(CurrentContext::class);
            $this->app->make(BatchedPageCacheInvalidator::class)->finishRequest($context->has() && $context->actor()->type === ActorType::Staff);
        });
        $this->app->terminating($flushPageCacheTags);
        Queue::after($flushPageCacheTags);
        Queue::failing($flushPageCacheTags);

        // Deploy (`optimize`, `view:cache`…) có thể đổi view/cấu hình mà không đổi version hay asset: xoá ngay cache trang và
        // báo CDN. Bảng cache chưa có (cài mới, chạy optimize trước migrate) thì bỏ qua, không làm hỏng lệnh deploy.
        Event::listen(CommandFinished::class, function (CommandFinished $event): void {
            if ($event->exitCode !== 0 || ! in_array($event->command, self::PAGE_CACHE_FLUSHING_COMMANDS, true)) {
                return;
            }
            try {
                $this->app->make(PageCacheInvalidator::class)->flush();
            } catch (Throwable) {
                // Chưa có store/bảng cache: không có gì để xoá.
            }
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('vani:idempotency:prune')->hourly()->withoutOverlapping()->onOneServer();
            $schedule->command('vani:page-cache:invalidate')->everyMinute()->withoutOverlapping()->onOneServer();
        });

        if ($this->app->runningInConsole()) {
            $this->commands([PruneIdempotencyKeysCommand::class, InvalidatePageCacheCommand::class]);
        }

        $this->bootModuleResources();
    }
}
