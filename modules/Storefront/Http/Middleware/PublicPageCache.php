<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Pipeline\Pipeline;
use Illuminate\Routing\Router;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Modules\Catalog\Contracts\PublicationSchedule;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Application\PageCache\RequestPageTags;
use Modules\Shared\Contracts\Metrics;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Shared\Http\SessionlessRoutes;
use Modules\Storefront\Application\Maintenance;
use Modules\Storefront\Application\PageCache\PageCacheKey;
use Modules\Storefront\Application\PageCache\PageCacheStore;
use Modules\Storefront\Contracts\PublicPageExpiry;
use Symfony\Component\HttpFoundation\Response;

/**
 * Trang công khai (trang chủ, danh mục, thương hiệu, tìm kiếm, sản phẩm, trang plugin `cacheable`) — storefront §5, ADR-051.
 * Chế độ `vanishop.storefront.page_cache.mode` (`VANI_PAGE_CACHE`):
 *
 * - **`app` (mặc định)**: không phiên, HTML giống nhau với mọi khách, không `Set-Cookie`; phần riêng của khách do JS lấy từ
 *   `GET /_vani/phien`. HTML lưu trong ứng dụng kèm tag dữ liệu đã dùng, hết hiệu lực khi tag bị xoá (lô mỗi phút) hoặc
 *   tới hạn tối đa. Header `X-Vani-Cache: HIT | MISS | BYPASS` (+ `X-Vani-Cache-Reason`).
 * - **`cdn`**: như `app`, thêm `Cache-Control: public, s-maxage` và header danh sách tag cho CDN.
 * - **`off`**: chạy lại phiên + nhận diện khách như mọi trang khác → HTML render sẵn phần riêng của khách, không cache.
 *
 * Không lưu: request khác GET/HEAD, phản hồi khác 200, link xem trước có chữ ký, tìm kiếm có từ khoá, quá nhiều giá trị
 * lọc, trang có khối gọi `PageCacheTags::uncacheable()`, nhân viên đang xem trước khi bảo trì.
 */
final class PublicPageCache
{
    /** Có mặt khi trang chạy không phiên (`app`, `cdn`): HTML chung cho mọi khách. */
    public const ATTRIBUTE = 'vani.page_cache';

    /** Có mặt trên mọi trang công khai, ở mọi chế độ (SEO: canonical, hreflang). */
    public const PUBLIC_PAGE = 'vani.public_page';

    /** Middleware bỏ khỏi route trang công khai. */
    public const SESSIONLESS = [...SessionlessRoutes::MIDDLEWARE, 'vani.customer-session'];

    public const MODE_APP = 'app';

    public const MODE_CDN = 'cdn';

    public const MODE_OFF = 'off';

    public const HEADER = 'X-Vani-Cache';

    /** Tag của trang trả về (gỡ lỗi: `vani:page-cache:tags`). */
    public const TAGS_ATTRIBUTE = 'vani.page_cache.tags';

    public function __construct(
        private readonly Router $router,
        private readonly PageCacheKey $keys,
        private readonly PageCacheStore $store,
        private readonly RequestPageTags $tags,
        private readonly Metrics $metrics,
    ) {}

    public static function mode(): string
    {
        $mode = (string) config('vanishop.storefront.page_cache.mode', self::MODE_APP);

        return in_array($mode, [self::MODE_APP, self::MODE_CDN, self::MODE_OFF], true) ? $mode : self::MODE_APP;
    }

    /** Trang công khai chạy không phiên (HTML chung cho mọi khách). */
    public static function sessionless(): bool
    {
        return self::mode() !== self::MODE_OFF;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set(self::PUBLIC_PAGE, true);
        if (! self::sessionless()) {
            return $this->withSession($request, $next);
        }

        $request->attributes->set(self::ATTRIBUTE, true);
        View::share('errors', new ViewErrorBag);
        $this->tags->reset();

        $reason = $this->bypassReason($request);
        if ($reason !== null) {
            return $this->finish($request, $this->render($request, $next), 'BYPASS', $reason, shareable: false);
        }
        $key = $this->keys->for($request, $reason);
        if ($key === null) {
            // Tìm kiếm, lọc nhiều: không lưu trong ứng dụng (quá nhiều tổ hợp); CDN vẫn cache được nếu trang dùng chung.
            $response = $this->render($request, $next);
            $shareable = $this->unshareableReason($request, $response) === null;

            return $this->finish($request, $response, 'BYPASS', $reason, $shareable, $this->collectedTags(), $shareable ? time() + $this->ttl($request) : null);
        }

        $entry = $this->store->get($key, $schedulerStale);
        if ($schedulerStale) {
            app()->terminating(fn () => app(BatchedPageCacheInvalidator::class)->processWhenStale());
        }
        if ($entry !== null) {
            return $this->finish($request, $this->fromEntry($entry), 'HIT', null, true, $entry['tags'], $entry['expires_at']);
        }

        $result = $this->store->rendering(
            $key,
            fn (bool $owner): array => $this->renderAndStore($request, $next, $key, $owner),
            fn (): ?array => $this->store->get($key),
        );
        if (isset($result['content'])) {
            /** @var array{content: string, status: int, headers: array<string, string>, tags: list<string>, expires_at: int} $result */
            return $this->finish($request, $this->fromEntry($result), 'HIT', null, true, $result['tags'], $result['expires_at']);
        }

        /** @var array{response: Response, stored: bool, reason: ?string, tags: list<string>, expires_at: ?int} $result */
        return $this->finish($request, $result['response'], $result['stored'] ? 'MISS' : 'BYPASS', $result['reason'], $result['expires_at'] !== null, $result['tags'], $result['expires_at']);
    }

    /**
     * @return array{response: Response, stored: bool, reason: ?string, tags: list<string>, expires_at: ?int}
     */
    private function renderAndStore(Request $request, Closure $next, string $key, bool $owner): array
    {
        $generation = $this->store->generation();
        $response = $this->render($request, $next);
        $tags = $this->collectedTags();

        $reason = $this->unshareableReason($request, $response);
        if ($reason !== null) {
            return ['response' => $response, 'stored' => false, 'reason' => $reason, 'tags' => $tags, 'expires_at' => null];
        }

        $ttl = $this->ttl($request);
        // Request khác đang render cùng trang (chờ quá hạn): không ghi đè, vẫn là trang dùng chung.
        $stored = $owner && $ttl > 0 && $this->store->put(
            $key, (string) $response->getContent(), $response->getStatusCode(),
            array_filter(['Content-Type' => (string) $response->headers->get('Content-Type')]), $tags, $ttl, $generation,
        );
        $reason = match (true) {
            $stored => null,
            ! $owner => 'busy',
            $ttl <= 0 => 'expired',
            default => 'stale_render',
        };

        return ['response' => $response, 'stored' => $stored, 'reason' => $reason, 'tags' => $tags, 'expires_at' => time() + max(0, $ttl)];
    }

    /**
     * Lý do trang **không** dùng chung được cho mọi khách (không lưu, không cho CDN cache), hoặc null.
     */
    private function unshareableReason(Request $request, Response $response): ?string
    {
        return match (true) {
            $response->getStatusCode() !== 200 => 'status',
            ! $request->isMethod('GET') => 'method',
            $response->getContent() === false => 'stream',
            default => $this->tags->uncacheableReason(),
        };
    }

    /**
     * @return list<string>
     */
    private function collectedTags(): array
    {
        return [PageCacheTags::EVERYTHING, ...$this->tags->tags((int) config('vanishop.storefront.page_cache.max_product_tags', 200))];
    }

    private function render(Request $request, Closure $next): Response
    {
        $response = $next($request);
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }

        return $response;
    }

    private function bypassReason(Request $request): ?string
    {
        return match (true) {
            ! $request->isMethodCacheable() => 'method',
            $request->query->has('signature') => 'preview',
            // Nhân viên xem trước khi bảo trì: không đọc/ghi bản dùng chung.
            $request->cookies->has(Maintenance::COOKIE) => 'maintenance_preview',
            default => null,
        };
    }

    /**
     * Hạn bản cache: tối đa `ttl`, ngắn lại tới mốc đổi nội dung sớm nhất (lịch thông báo của plugin, bảng giá, khung giờ
     * hiển thị sản phẩm).
     */
    private function ttl(Request $request): int
    {
        $now = time();
        $until = array_filter([
            PublicPageExpiry::timestamp($request),
            app(PriceListSchedule::class)->nextChangeAfter($now),
            app(PublicationSchedule::class)->nextChangeAfter($now),
        ], fn (?int $at): bool => $at !== null);
        $ttl = (int) config('vanishop.storefront.page_cache.ttl', 3600);

        return $until === [] ? $ttl : min($ttl, min($until) - $now);
    }

    /**
     * @param  array{content: string, status: int, headers: array<string, string>}  $entry
     */
    private function fromEntry(array $entry): Response
    {
        return new IlluminateResponse($entry['content'], $entry['status'], $entry['headers']);
    }

    /**
     * CDN không giữ lâu hơn bản trong ứng dụng (`$expiresAt` đã gồm mốc đổi nội dung).
     *
     * @param  list<string>  $tags
     */
    private function finish(Request $request, Response $response, string $state, ?string $reason, bool $shareable, array $tags = [], ?int $expiresAt = null): Response
    {
        $this->metrics->increment('page_cache.requests', key: strtolower($state));
        $request->attributes->set(self::TAGS_ATTRIBUTE, $tags);
        $response->headers->set(self::HEADER, $state);
        if ($reason !== null) {
            $response->headers->set(self::HEADER.'-Reason', $reason);
        }
        // Ngôn ngữ nằm trong URL (ADR-038) nên không Vary theo header ngôn ngữ.
        $response->headers->set('Vary', 'Accept-Encoding');

        if (! $shareable || self::mode() !== self::MODE_CDN) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }

        $config = (array) config('vanishop.storefront.page_cache');
        $fresh = (int) ($config['s_maxage'] ?? 300);
        $stale = (int) ($config['stale_while_revalidate'] ?? 600);
        if ($expiresAt !== null) {
            $remaining = max(0, $expiresAt - time());
            $fresh = min($fresh, $remaining);
            $stale = min($stale, $remaining - $fresh);
        }
        $response->headers->set('Cache-Control', sprintf('public, max-age=0, s-maxage=%d, stale-while-revalidate=%d', $fresh, $stale));
        $header = (string) ($config['cdn_tag_header'] ?? '');
        if ($header !== '' && $tags !== []) {
            $response->headers->set($header, implode(',', $tags));
        }

        return $response;
    }

    /**
     * `off`: chạy phần nhóm `web` đã bỏ (trang công khai chỉ có GET nên không cần kiểm CSRF) rồi nhận diện khách.
     */
    private function withSession(Request $request, Closure $next): Response
    {
        $customerSession = $this->router->getMiddleware()['vani.customer-session'];

        $response = (new Pipeline(app()))
            ->send($request)
            ->through([StartSession::class, ShareErrorsFromSession::class, $customerSession])
            ->then($next);
        $response->headers->set('Cache-Control', 'private, no-store');

        return $response;
    }
}
