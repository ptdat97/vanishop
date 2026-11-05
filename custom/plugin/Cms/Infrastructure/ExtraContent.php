<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\MediaUrls;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;
use Plugin\Cms\CmsServiceProvider;
use Plugin\Cms\Persistence\CategoryBanner;
use Plugin\Cms\Persistence\ProductExtra;

/**
 * Nội dung thêm của cửa hàng (vani.cms 1.5, đặc tả store-content.md): thanh thông báo nhiều dòng ở đầu trang, hộp ưu
 * đãi dùng chung ở trang sản phẩm, điểm nổi bật theo sản phẩm, banner đầu trang danh mục. Mọi nội dung như nhau cho
 * mọi khách nên nằm được trong HTML cache CDN.
 */
final class ExtraContent
{
    public const MAX_HIGHLIGHTS = 8;

    public const HIGHLIGHT_LENGTH = 160;

    public const BANNER_OWNER = 'plg.cms.category_banner';

    public const POSITIONS = ['after_price' => 'Sau giá', 'after_add_to_cart' => 'Sau nút thêm vào giỏ', 'after_details' => 'Sau mô tả chi tiết'];

    /** Thẻ được giữ trong một dòng thông báo (Markdown một dòng: đậm, nghiêng, link). */
    private const INLINE_TAGS = '<a><strong><em><code><del>';

    public function __construct(
        private readonly Markdown $markdown,
        private readonly MediaDirectory $media,
        private readonly MediaUrls $mediaUrls,
    ) {}

    /**
     * Thanh thông báo đang hiệu lực (bật, trong khung giờ, có ít nhất một dòng) hoặc null.
     *
     * @return array{lines: list<string>, pause_ms: int, fade_ms: int, bg: ?string, fg: ?string, until: ?int}|null
     */
    public function announcement(string $locale, ?DateTimeImmutable $now = null): ?array
    {
        if (! (bool) $this->setting('announcement_enabled', false)) {
            return null;
        }
        $now ??= StoreClock::now();
        $from = $this->storeTime($this->setting('announcement_from'));
        $until = $this->storeTime($this->setting('announcement_to'));
        if (($from !== null && $now < $from) || ($until !== null && $now >= $until)) {
            return null;
        }

        $lines = $this->lines((string) $this->localized('announcement_lines', $locale));
        $lines = array_values(array_filter(array_map(fn (string $line): string => $this->inline($line), $lines), fn (string $html): bool => trim(strip_tags($html)) !== ''));
        if ($lines === []) {
            return null;
        }

        return [
            'lines' => $lines,
            'pause_ms' => max(1000, min(15000, (int) $this->setting('announcement_pause_ms', 3000))),
            'fade_ms' => max(0, min(2000, (int) $this->setting('announcement_fade_ms', 500))),
            'bg' => self::color($this->setting('announcement_bg')),
            'fg' => self::color($this->setting('announcement_fg')),
            // Trang cache CDN có thể được phục vụ sau giờ kết thúc → JS ẩn thanh theo mốc này.
            'until' => $until?->getTimestamp(),
        ];
    }

    /**
     * Một dòng Markdown → HTML nội dòng an toàn (bỏ đoạn văn, ảnh, khối; link `javascript:` đã bị Markdown chặn).
     */
    public function inline(string $markdown): string
    {
        return trim(strip_tags($this->markdown->html($markdown), self::INLINE_TAGS));
    }

    public function promoPosition(): string
    {
        $position = (string) $this->setting('promo_box_position', 'after_add_to_cart');

        return array_key_exists($position, self::POSITIONS) ? $position : 'after_add_to_cart';
    }

    /**
     * HTML hộp ưu đãi dùng chung (đã render Markdown) hoặc null khi tắt/trống.
     */
    public function promoBox(string $locale): ?string
    {
        $markdown = trim((string) $this->localized('promo_box_markdown', $locale));

        return (bool) $this->setting('promo_box_enabled', false) && $markdown !== '' ? $this->markdown->html($markdown) : null;
    }

    /**
     * Dữ liệu `extensions["vani.cms"]` của trang sản phẩm: điểm nổi bật + hộp ưu đãi (null khi tắt hoặc sản phẩm ẩn hộp).
     *
     * @param  list<int>  $styleIds
     * @return array<int, array{highlights: list<string>, promo_box: ?string}>
     */
    public function forProducts(array $styleIds, string $locale): array
    {
        $extras = ProductExtra::query()->whereIn('style_id', $styleIds)->get()->keyBy('style_id');
        $promoBox = $this->promoBox($locale);
        $default = StoreLocale::default();
        $result = [];
        foreach ($styleIds as $id) {
            $extra = $extras->get($id);
            $highlights = $extra === null ? [] : (($extra->highlights[$locale] ?? []) ?: ($extra->highlights[$default] ?? []));
            $box = $extra?->hide_promo_box ? null : $promoBox;
            if ($highlights !== [] || $box !== null) {
                $result[$id] = ['highlights' => array_values($highlights), 'promo_box' => $box];
            }
        }

        return $result;
    }

    /**
     * Giá trị cho phần form "Nội dung thêm" ở form sản phẩm.
     *
     * @return array<string, mixed>
     */
    public function productForm(int $styleId): array
    {
        $extra = ProductExtra::query()->where('style_id', $styleId)->first();
        $values = ['hide_promo_box' => (bool) $extra?->hide_promo_box];
        foreach (StoreLocale::supported() as $locale) {
            $values[self::highlightKey($locale)] = implode("\n", $extra->highlights[$locale] ?? []);
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function saveProduct(int $styleId, array $values): void
    {
        $highlights = [];
        foreach (StoreLocale::supported() as $locale) {
            $lines = $this->highlights((string) ($values[self::highlightKey($locale)] ?? ''));
            if ($lines !== []) {
                $highlights[$locale] = $lines;
            }
        }
        $hide = (bool) ($values['hide_promo_box'] ?? false);
        if ($highlights === [] && ! $hide) {
            ProductExtra::query()->where('style_id', $styleId)->delete();

            return;
        }
        ProductExtra::query()->updateOrCreate(['style_id' => $styleId], ['highlights' => $highlights === [] ? null : $highlights, 'hide_promo_box' => $hide]);
    }

    /**
     * Mỗi dòng một điểm nổi bật: bỏ dòng trống, cắt độ dài, tối đa 8.
     *
     * @return list<string>
     */
    public function highlights(string $text): array
    {
        $lines = array_map(fn (string $line): string => mb_substr(trim($line), 0, self::HIGHLIGHT_LENGTH), $this->lines($text));

        return array_slice(array_values(array_filter($lines, fn (string $line): bool => $line !== '')), 0, self::MAX_HIGHLIGHTS);
    }

    public static function highlightKey(string $locale): string
    {
        return 'highlights_'.strtolower(str_replace('-', '_', $locale));
    }

    /**
     * Banner đang bật của danh mục, đã trình bày cho storefront.
     *
     * @return array{title: ?string, html: ?string, button_text: ?string, button_url: ?string, desktop: ?string, mobile: ?string}|null
     */
    public function banner(int $categoryId, string $locale): ?array
    {
        $banner = CategoryBanner::query()->where('category_id', $categoryId)->where('active', true)->first();
        if ($banner === null) {
            return null;
        }
        $default = StoreLocale::default();
        $markdown = $banner->text('markdown', $locale, $default);
        $desktop = $banner->desktop_media_id === null ? null : $this->mediaUrls->image($banner->desktop_media_id, 1600);
        $buttonText = $banner->text('button_text', $locale, $default);
        $presented = [
            'title' => $banner->text('title', $locale, $default),
            'html' => $markdown === null ? null : $this->markdown->html($markdown),
            'button_text' => $buttonText,
            'button_url' => $buttonText !== null && self::allowedUrl($banner->button_url) ? $banner->button_url : null,
            'desktop' => $desktop,
            // Không có ảnh mobile → dùng ảnh desktop (§8-4).
            'mobile' => ($banner->mobile_media_id === null ? null : $this->mediaUrls->image($banner->mobile_media_id, 800)) ?? ($banner->desktop_media_id === null ? null : $this->mediaUrls->image($banner->desktop_media_id, 800)),
        ];

        return $presented['title'] === null && $presented['html'] === null && $desktop === null ? null : $presented;
    }

    /**
     * @param  array{content?: array<string, array<string, ?string>>, button_url?: ?string, desktop_media_id?: ?int, mobile_media_id?: ?int, active?: bool}  $data
     */
    public function saveBanner(?CategoryBanner $banner, int $categoryId, array $data): CategoryBanner
    {
        if (CategoryBanner::query()->where('category_id', $categoryId)->when($banner !== null, fn ($query) => $query->whereKeyNot($banner->id))->exists()) {
            throw ValidationException::withMessages(['category_id' => 'Danh mục này đã có banner.']);
        }
        $url = trim((string) ($data['button_url'] ?? ''));
        if ($url !== '' && ! self::allowedUrl($url)) {
            throw ValidationException::withMessages(['button_url' => 'Link nút phải là đường dẫn trong cửa hàng (bắt đầu bằng /) hoặc https://.']);
        }
        $content = [];
        foreach (StoreLocale::supported() as $locale) {
            $texts = array_map(fn (mixed $value): ?string => is_string($value) && trim($value) !== '' ? trim($value) : null, array_intersect_key((array) ($data['content'][$locale] ?? []), array_flip(['title', 'markdown', 'button_text'])));
            if (array_filter($texts) !== []) {
                $content[$locale] = $texts;
            }
        }
        $desktop = isset($data['desktop_media_id']) && (int) $data['desktop_media_id'] > 0 ? (int) $data['desktop_media_id'] : null;
        $mobile = isset($data['mobile_media_id']) && (int) $data['mobile_media_id'] > 0 ? (int) $data['mobile_media_id'] : null;
        $ids = array_values(array_unique(array_filter([$desktop, $mobile])));
        if ($ids !== [] && count($this->media->find($ids)) !== count($ids)) {
            throw ValidationException::withMessages(['desktop_media_id' => 'Có ảnh không còn trong Thư viện ảnh.']);
        }
        if ($mobile !== null && $desktop === null) {
            throw ValidationException::withMessages(['desktop_media_id' => 'Chọn ảnh desktop trước khi chọn ảnh mobile.']);
        }

        return DB::transaction(function () use ($banner, $categoryId, $content, $url, $desktop, $mobile, $data): CategoryBanner {
            $banner ??= new CategoryBanner;
            $banner->fill([
                'category_id' => $categoryId, 'content' => $content === [] ? null : $content, 'button_url' => $url === '' ? null : $url,
                'desktop_media_id' => $desktop, 'mobile_media_id' => $mobile, 'active' => (bool) ($data['active'] ?? true),
            ])->save();
            $this->media->syncUsages(self::BANNER_OWNER, $banner->id, 'desktop', $desktop === null ? [] : [$desktop]);
            $this->media->syncUsages(self::BANNER_OWNER, $banner->id, 'mobile', $mobile === null ? [] : [$mobile]);

            return $banner->refresh();
        });
    }

    public function deleteBanner(CategoryBanner $banner): void
    {
        DB::transaction(function () use ($banner): void {
            $banner->delete();
            $this->media->releaseUsages(self::BANNER_OWNER, $banner->id);
        });
    }

    /**
     * Link nút banner: đường dẫn trong cửa hàng (`/…`, không phải `//host`) hoặc `https://`.
     */
    public static function allowedUrl(?string $url): bool
    {
        if ($url === null || $url === '' || preg_match('/[\s\x00-\x1f]/', $url) === 1) {
            return false;
        }

        return (str_starts_with($url, '/') && ! str_starts_with($url, '//') && ! str_starts_with($url, '/\\'))
            || (str_starts_with(strtolower($url), 'https://') && filter_var($url, FILTER_VALIDATE_URL) !== false);
    }

    private static function color(mixed $value): ?string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1 ? $value : null;
    }

    /**
     * @return list<string>
     */
    private function lines(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/u', $text) ?: []), fn (string $line): bool => $line !== ''));
    }

    private function storeTime(mixed $value): ?DateTimeImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d H:i', trim($value), StoreClock::zone());

        return $parsed === false ? null : $parsed;
    }

    private function localized(string $key, string $locale): mixed
    {
        $value = $this->setting("{$key}.{$locale}");

        return is_string($value) && trim($value) !== '' ? $value : $this->setting($key.'.'.StoreLocale::default());
    }

    private function setting(string $key, mixed $default = null): mixed
    {
        return vani_setting(CmsServiceProvider::ID, $key, $default);
    }
}
