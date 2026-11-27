<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\PageCache;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Modules\Catalog\Contracts\PublicationSchedule;
use Modules\Pricing\Contracts\PriceListSchedule;
use Modules\Shared\Application\PageCache\BatchedPageCacheInvalidator;
use Modules\Shared\Application\PageCache\RequestPageTags;
use Modules\Shared\Contracts\Metrics;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Storefront\Application\Maintenance;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Contracts\PublicPageExpiry;
use Modules\Storefront\Http\Middleware\PublicPageCache;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Luồng cache dùng chung của trang HTML công khai và GET công khai của Storefront API (ADR-051 §1–§7): khoá, đọc bản còn
 * hiệu lực, chống dồn tải, lưu kèm tag, header `X-Vani-Cache`, header CDN.
 *
 * Khác nhau giữa hai loại (`$api`):
 * - HTML: bỏ mọi `Set-Cookie` (trang không phiên); `Vary: Accept-Encoding` (ngôn ngữ trong URL).
 * - API: giữ cookie phiên cùng nguồn trừ khi cho CDN cache; `Vary` thêm `X-Vani-Locale`; `ETag` + 304; chế độ `cdn` chỉ
 *   cho CDN cache khi giá công khai (ẩn giá thì khách đã đăng nhập qua phiên cùng nguồn nhận nội dung khác).
 */
final class CachedResponses
{
    public function __construct(
        private readonly PageCacheKey $keys,
        private readonly PageCacheStore $store,
        private readonly RequestPageTags $tags,
        private readonly Metrics $metrics,
        private readonly PriceVisibility $prices,
    ) {}

    /**
     * @param  string|null  $bypass  lý do không dùng bản chung do nơi gọi xác định (vd. khách đã đăng nhập)
     */
    public function respond(Request $request, Closure $next, ?string $bypass = null, bool $api = false): Response
    {
        $this->tags->reset();
        $this->keys->withoutTracking($request);
        $reason = $bypass ?? $this->bypassReason($request);
        if ($reason !== null) {
            return $this->finish($request, $this->render($request, $next, $api), 'BYPASS', $reason, false, $api);
        }
        $key = $this->keys->for($request, $reason);
        if ($key === null) {
            // Tìm kiếm, lọc nhiều: không lưu trong ứng dụng (quá nhiều tổ hợp); CDN vẫn cache được nếu trang dùng chung.
            $response = $this->render($request, $next, $api);
            $shareable = $this->unshareableReason($request, $response) === null;

            return $this->finish($request, $response, 'BYPASS', $reason, $shareable, $api, $this->collectedTags(), $shareable ? now()->getTimestamp() + $this->ttl($request) : null);
        }

        $entry = $this->store->get($key, $schedulerStale);
        if ($this->store->failed()) {
            // Store cache lỗi (Redis mất kết nối…): vẫn phục vụ trang, chỉ không cache.
            return $this->finish($request, $this->render($request, $next, $api), 'BYPASS', 'cache_error', false, $api);
        }
        if ($schedulerStale) {
            app()->terminating(function (): void {
                try {
                    app(BatchedPageCacheInvalidator::class)->processWhenStale();
                } catch (Throwable $exception) {
                    report($exception);
                }
            });
        }
        if ($entry !== null) {
            return $this->finish($request, $this->fromEntry($entry), 'HIT', null, true, $api, $entry['tags'], $entry['expires_at']);
        }

        $result = $this->store->rendering(
            $key,
            fn (bool $owner): array => $this->renderAndStore($request, $next, $key, $owner, $api),
            fn (): ?array => $this->store->get($key),
        );
        if (isset($result['content'])) {
            /** @var array{content: string, status: int, headers: array<string, string>, tags: list<string>, expires_at: int} $result */
            return $this->finish($request, $this->fromEntry($result), 'HIT', null, true, $api, $result['tags'], $result['expires_at']);
        }

        /** @var array{response: Response, stored: bool, reason: ?string, tags: list<string>, expires_at: ?int} $result */
        return $this->finish($request, $result['response'], $result['stored'] ? 'MISS' : 'BYPASS', $result['reason'], $result['expires_at'] !== null, $api, $result['tags'], $result['expires_at']);
    }

    /**
     * @return array{response: Response, stored: bool, reason: ?string, tags: list<string>, expires_at: ?int}
     */
    private function renderAndStore(Request $request, Closure $next, string $key, bool $owner, bool $api): array
    {
        $generation = $this->store->generation();
        $response = $this->render($request, $next, $api);
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
            $this->store->failed() => 'cache_error',
            ! $owner => 'busy',
            $ttl <= 0 => 'expired',
            default => 'stale_render',
        };

        return ['response' => $response, 'stored' => $stored, 'reason' => $reason, 'tags' => $tags, 'expires_at' => now()->getTimestamp() + max(0, $ttl)];
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

    /**
     * HTML: bỏ mọi cookie. API: giữ cookie phiên cùng nguồn (bản lưu chỉ có nội dung, không kèm cookie).
     */
    private function render(Request $request, Closure $next, bool $api): Response
    {
        $response = $next($request);
        if (! $api) {
            $this->withoutCookies($response);
        }

        return $response;
    }

    private function withoutCookies(Response $response): void
    {
        foreach ($response->headers->getCookies() as $cookie) {
            $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
        }
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
        $now = now()->getTimestamp();
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
    private function finish(Request $request, Response $response, string $state, ?string $reason, bool $shareable, bool $api, array $tags = [], ?int $expiresAt = null): Response
    {
        $this->metrics->increment('page_cache.requests', key: strtolower($state));
        $request->attributes->set(PublicPageCache::TAGS_ATTRIBUTE, $tags);
        $response->headers->set(PublicPageCache::HEADER, $state);
        if ($reason !== null) {
            $response->headers->set(PublicPageCache::HEADER.'-Reason', $reason);
        }
        // HTML: ngôn ngữ nằm trong URL (ADR-038). API: ngôn ngữ theo header X-Vani-Locale.
        $response->headers->set('Vary', $api ? 'Accept-Encoding, X-Vani-Locale' : 'Accept-Encoding');
        if ($api && $shareable) {
            $response = $this->withEtag($request, $response);
        }

        if (! $shareable || PublicPageCache::mode() !== PublicPageCache::MODE_CDN || ($api && $this->prices->membersOnly())) {
            $response->headers->set('Cache-Control', 'private, no-store');

            return $response;
        }
        if ($api) {
            $this->withoutCookies($response);
        }

        $config = (array) config('vanishop.storefront.page_cache');
        $fresh = (int) ($config['s_maxage'] ?? 300);
        $stale = (int) ($config['stale_while_revalidate'] ?? 600);
        if ($expiresAt !== null) {
            $remaining = max(0, $expiresAt - now()->getTimestamp());
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
     * `ETag` theo nội dung; `If-None-Match` khớp → 304 không thân (trình duyệt, app di động đỡ tải lại).
     */
    private function withEtag(Request $request, Response $response): Response
    {
        $etag = '"'.sha1((string) $response->getContent()).'"';
        $response->headers->set('ETag', $etag);
        if (in_array($etag, $request->getETags(), true)) {
            $response->setNotModified();
        }

        return $response;
    }
}
