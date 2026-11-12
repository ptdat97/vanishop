<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Menus;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Support\StoreClock;
use Modules\Storefront\Application\Theme\Themes;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\LinkResolver;
use Modules\Tenancy\Contracts\Settings;
use Throwable;

/**
 * Menu theo vị trí do theme khai báo (`theme.json` → `menus`), lưu ở cấu hình cửa hàng `core.storefront.menu.<vị trí>`
 * (module Storefront không có bảng, ADR-021): bản nháp, bản đã xuất bản và tối đa 10 bản xuất bản trước để khôi phục.
 * Storefront chỉ đọc bản đã xuất bản.
 *
 * Mục: `{id, link: LinkTarget|null, label: {locale: nhãn}, badge: null|new|sale, image_media_id (chỉ cấp 1), enabled,
 * visible_from, visible_to (giờ cửa hàng, `Y-m-d\TH:i`), children}`. Mục không có link là tiêu đề nhóm (cần nhãn).
 *
 * @phpstan-type MenuItem array{id: string, link: array{type: string, value: string, label: array<string, string>}|null, label: array<string, string>, badge: ?string, image_media_id: ?int, enabled: bool, visible_from: ?string, visible_to: ?string, children: list<mixed>}
 */
final class MenuRepository
{
    public const MAX_ITEMS = 200;

    public const MAX_VERSIONS = 10;

    public const BADGES = ['new', 'sale'];

    private const SETTING = 'storefront.menu.';

    public function __construct(
        private readonly Settings $settings,
        private readonly Themes $themes,
        private readonly LinkResolver $links,
    ) {}

    /**
     * @return array<string, array{label: string, depth: int}>
     */
    public function locations(): array
    {
        return $this->themes->menuLocations();
    }

    /**
     * @return array{draft: list<array<string, mixed>>, published: list<array<string, mixed>>|null, published_at: ?string, versions: list<array{items: list<array<string, mixed>>, published_at: ?string}>, updated_at: ?string}
     */
    public function state(string $location): array
    {
        $this->location($location);
        $stored = $this->settings->get('core', self::SETTING.$location);
        $stored = is_array($stored) ? $stored : [];

        return [
            'draft' => array_values((array) ($stored['draft'] ?? ($stored['published'] ?? []))),
            'published' => isset($stored['published']) && is_array($stored['published']) ? array_values($stored['published']) : null,
            'published_at' => $stored['published_at'] ?? null,
            'versions' => array_values(array_filter((array) ($stored['versions'] ?? []), 'is_array')),
            'updated_at' => $stored['updated_at'] ?? null,
        ];
    }

    /**
     * Bản đã xuất bản; null nếu vị trí chưa có menu (theme dùng mặc định của nó).
     *
     * @return list<array<string, mixed>>|null
     */
    public function published(string $location): ?array
    {
        return isset($this->locations()[$location]) ? $this->state($location)['published'] : null;
    }

    /**
     * Kiểm tra + chuẩn hoá rồi lưu nháp.
     *
     * @param  list<mixed>  $items
     */
    public function saveDraft(string $location, array $items): void
    {
        $depth = $this->location($location)['depth'];
        $count = 0;
        $clean = $this->normalize($items, 1, $depth, 'items', $count);

        $this->write($location, ['draft' => $clean, 'updated_at' => now()->toIso8601String()]);
    }

    /** Xuất bản bản nháp; bản đang xuất bản vào lịch sử (giữ 10 bản gần nhất). */
    public function publish(string $location): void
    {
        $state = $this->state($location);
        $versions = $state['versions'];
        if ($state['published'] !== null) {
            array_unshift($versions, ['items' => $state['published'], 'published_at' => $state['published_at']]);
        }

        $this->write($location, [
            'published' => $state['draft'],
            'published_at' => now()->toIso8601String(),
            'versions' => array_slice($versions, 0, self::MAX_VERSIONS),
        ]);
    }

    /** Bỏ thay đổi chưa xuất bản. */
    public function discardDraft(string $location): void
    {
        $this->write($location, ['draft' => $this->state($location)['published'] ?? [], 'updated_at' => now()->toIso8601String()]);
    }

    /** Đưa một bản xuất bản trước vào nháp (xuất bản lại để áp dụng). */
    public function restore(string $location, int $version): void
    {
        $versions = $this->state($location)['versions'];
        $items = $versions[$version]['items'] ?? throw ValidationException::withMessages(['version' => 'Không có phiên bản này.']);

        $this->write($location, ['draft' => $items, 'updated_at' => now()->toIso8601String()]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function write(string $location, array $changes): void
    {
        $stored = $this->settings->get('core', self::SETTING.$location);
        $this->settings->set('core', self::SETTING.$location, [...(is_array($stored) ? $stored : []), ...$changes]);
    }

    /**
     * @return array{label: string, depth: int}
     */
    private function location(string $location): array
    {
        return $this->locations()[$location] ?? throw ValidationException::withMessages(['location' => 'Theme không có vị trí menu này.']);
    }

    /**
     * @param  array<array-key, mixed>  $items  dữ liệu gửi lên (JSON object/mảng bất kỳ) — chuẩn hoá về danh sách
     * @return list<array<string, mixed>>
     */
    private function normalize(array $items, int $level, int $maxDepth, string $path, int &$count): array
    {
        $clean = [];
        foreach (array_values($items) as $index => $item) {
            $key = "{$path}.{$index}";
            $item = is_array($item) ? $item : [];
            if (++$count > self::MAX_ITEMS) {
                throw ValidationException::withMessages(['items' => 'Tối đa '.self::MAX_ITEMS.' mục mỗi menu.']);
            }

            $label = array_filter(array_map(fn (mixed $text): string => mb_substr(trim((string) $text), 0, 80), (array) ($item['label'] ?? [])), fn (string $text): bool => $text !== '');
            $link = is_array($item['link'] ?? null) ? LinkTarget::fromArray($item['link']) : null;
            if ($link !== null && ! $this->links->accepts($link)) {
                throw ValidationException::withMessages(["{$key}.link" => 'Link không hợp lệ hoặc loại link không còn.']);
            }
            if ($link === null && $label === []) {
                throw ValidationException::withMessages(["{$key}.label" => 'Mục không có link cần nhãn (tiêu đề nhóm).']);
            }

            $badge = $item['badge'] ?? null;
            if ($badge !== null && $badge !== '' && ! in_array($badge, self::BADGES, true)) {
                throw ValidationException::withMessages(["{$key}.badge" => 'Huy hiệu không hợp lệ.']);
            }

            [$from, $to] = [$this->time($item['visible_from'] ?? null, "{$key}.visible_from"), $this->time($item['visible_to'] ?? null, "{$key}.visible_to")];
            if ($from !== null && $to !== null && $from >= $to) {
                throw ValidationException::withMessages(["{$key}.visible_to" => 'Thời điểm kết thúc phải sau thời điểm bắt đầu.']);
            }

            $children = (array) ($item['children'] ?? []);
            if ($children !== [] && $level >= $maxDepth) {
                throw ValidationException::withMessages(["{$key}.children" => "Vị trí này tối đa {$maxDepth} cấp."]);
            }

            $media = $item['image_media_id'] ?? null;
            $clean[] = [
                'id' => preg_match('/^[a-z0-9]{1,24}$/', (string) ($item['id'] ?? '')) === 1 ? (string) $item['id'] : bin2hex(random_bytes(6)),
                'link' => $link?->toArray(),
                'label' => $label,
                'badge' => $badge === '' ? null : $badge,
                'image_media_id' => $level === 1 && is_numeric($media) && (int) $media > 0 ? (int) $media : null,
                'enabled' => (bool) ($item['enabled'] ?? true),
                'visible_from' => $from,
                'visible_to' => $to,
                'children' => $this->normalize($children, $level + 1, $maxDepth, "{$key}.children", $count),
            ];
        }

        return $clean;
    }

    private function time(mixed $value, string $key): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Carbon::parse((string) $value, StoreClock::timezone())->format('Y-m-d\TH:i');
        } catch (Throwable) {
            throw ValidationException::withMessages([$key => 'Thời điểm không hợp lệ.']);
        }
    }
}
