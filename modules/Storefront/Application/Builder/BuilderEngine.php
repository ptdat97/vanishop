<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Contracts\PageCacheTags;
use Modules\Storefront\Application\PageBlocks;
use Modules\Tenancy\Contracts\Settings;

/**
 * Builder trang chủ (Admin → Giao diện → Trang chủ): danh sách block admin kéo thả, lưu ở `core.storefront.builder.home`.
 * Schema, validation và renderer block dùng chung {@see PageBlocks}. Trang khác không có Builder: plugin và theme thêm nội
 * dung cố định bằng code tại slot (`onSlot`, `<x-vani::hook-slot>`). Scoped theo request: cấu hình đã đọc được nhớ lại.
 */
final class BuilderEngine
{
    public const HOME = 'home';

    /** Chủ sở hữu ghi nhận ảnh đang dùng (Thư viện ảnh không cho xoá); role = `home`. */
    public const MEDIA_OWNER = 'storefront.builder';

    private const SETTING = 'storefront.builder.'.self::HOME;

    /** @var list<array{type: string, config: array<string, mixed>}>|null */
    private ?array $stored = null;

    private bool $loaded = false;

    public function __construct(
        private readonly Settings $settings,
        private readonly MediaDirectory $media,
        private readonly PageBlocks $blocks,
    ) {}

    /**
     * @return list<array{type: string, config: array<string, mixed>}>|null null = chưa cấu hình (theme dùng trang chủ mặc định)
     */
    public function blocks(): ?array
    {
        // Cache trang (ADR-051): lưu Builder chỉ xoá trang có vùng này.
        app(PageCacheTags::class)->add('builder:'.self::HOME);
        if (! $this->loaded) {
            $blocks = $this->settings->get('core', self::SETTING);
            $this->stored = is_array($blocks) ? array_values($blocks) : null;
            $this->loaded = true;
        }

        return $this->stored;
    }

    /**
     * @param  list<array{type?: mixed, config?: mixed}>  $blocks
     * @return list<array{type: string, config: array<string, mixed>}>
     */
    public function save(array $blocks): array
    {
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $this->blocks->validate($blocks);
        $this->settings->set('core', self::SETTING, $clean);
        $this->media->syncUsages(self::MEDIA_OWNER, 1, self::HOME, $mediaIds);
        [$this->stored, $this->loaded] = [$clean, true];

        return $clean;
    }

    public function reset(): void
    {
        $this->settings->forget('core', self::SETTING);
        $this->media->syncUsages(self::MEDIA_OWNER, 1, self::HOME, []);
        [$this->stored, $this->loaded] = [null, true];
    }
}
