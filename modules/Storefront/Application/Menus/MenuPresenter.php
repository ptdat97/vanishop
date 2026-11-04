<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Menus;

use Modules\Catalog\Contracts\Data\MediaData;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreClock;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\Data\ResolvedLink;
use Modules\Storefront\Contracts\LinkResolver;

/**
 * Menu đã xuất bản để theme render: phân giải mọi link của menu một lần (một truy vấn mỗi loại), bỏ mục tắt, ngoài
 * khung giờ hiển thị, hoặc trỏ tới đối tượng đã xoá/ẩn (kèm cả nhánh con của nó). Ghi nhớ trong request.
 *
 * Mục trả về: `{label, url (null = tiêu đề nhóm), badge, image_url, children}`.
 */
final class MenuPresenter
{
    /** @var array<string, list<array<string, mixed>>|null> */
    private array $memo = [];

    public function __construct(
        private readonly MenuRepository $menus,
        private readonly LinkResolver $links,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * @return list<array{label: string, url: ?string, badge: ?string, image_url: ?string, children: list<mixed>}>|null null = vị trí chưa có menu xuất bản
     */
    public function items(string $location, string $locale): ?array
    {
        $key = "{$locale}|{$location}";
        if (array_key_exists($key, $this->memo)) {
            return $this->memo[$key];
        }

        $items = $this->menus->published($location);
        if ($items === null) {
            return $this->memo[$key] = null;
        }

        $targets = [];
        $mediaIds = [];
        $this->collect($items, $targets, $mediaIds);
        $resolved = $this->links->resolveMany($targets, $locale);
        $images = $mediaIds === [] ? [] : $this->media->find(array_values(array_unique($mediaIds)));

        return $this->memo[$key] = $this->present($items, $resolved, $images, $locale, StoreClock::now()->format('Y-m-d\TH:i'));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, LinkTarget>  $targets
     * @param  list<int>  $mediaIds
     */
    private function collect(array $items, array &$targets, array &$mediaIds): void
    {
        foreach ($items as $item) {
            if (is_array($item['link'] ?? null) && ($target = LinkTarget::fromArray($item['link'])) !== null) {
                $targets[(string) $item['id']] = $target;
            }
            if (is_int($item['image_media_id'] ?? null)) {
                $mediaIds[] = $item['image_media_id'];
            }
            $this->collect((array) ($item['children'] ?? []), $targets, $mediaIds);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @param  array<string, ResolvedLink>  $resolved
     * @param  array<int, MediaData>  $images
     * @return list<array{label: string, url: ?string, badge: ?string, image_url: ?string, children: list<mixed>}>
     */
    private function present(array $items, array $resolved, array $images, string $locale, string $now): array
    {
        $shown = [];
        foreach ($items as $item) {
            if (! ($item['enabled'] ?? true) || (($item['visible_from'] ?? null) !== null && $now < $item['visible_from']) || (($item['visible_to'] ?? null) !== null && $now >= $item['visible_to'])) {
                continue;
            }

            $link = $resolved[(string) $item['id']] ?? null;
            $override = $item['label'][$locale] ?? null;
            if ($link === null) {
                // Tiêu đề nhóm: cần nhãn theo ngôn ngữ (hoặc nhãn bất kỳ đã nhập).
                $label = $override ?? (is_array($item['label'] ?? null) ? reset($item['label']) : null);
                if (! is_string($label) || $label === '') {
                    continue;
                }
                $url = null;
            } elseif (! $link->exists) {
                continue;
            } else {
                $label = $override ?? $link->label;
                $url = $link->url;
            }

            $image = is_int($item['image_media_id'] ?? null) ? ($images[$item['image_media_id']] ?? null) : null;
            $shown[] = [
                'label' => $label,
                'url' => $url,
                'badge' => $item['badge'] ?? null,
                'image_url' => $image?->urlFor(480),
                'children' => $this->present((array) ($item['children'] ?? []), $resolved, $images, $locale, $now),
            ];
        }

        return $shown;
    }
}
