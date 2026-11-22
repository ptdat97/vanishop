<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Tenancy\Contracts\Settings;

/**
 * Builder Engine: quản lý các {@see BuilderSurface} (Core + plugin), lưu cấu hình block theo surface và render block
 * của vùng slot tại `<x-vani::hook-slot>`. Schema, validation và renderer block dùng chung {@see PageBlocks}.
 * Scoped theo request: danh sách surface, chỉ mục slot và cấu hình đã đọc được nhớ trong request.
 */
final class BuilderEngine
{
    public const HOME = 'home';

    /** Chủ sở hữu ghi nhận ảnh đang dùng (Thư viện ảnh không cho xoá); role = mã surface. */
    public const MEDIA_OWNER = 'storefront.builder';

    private const SETTING_PREFIX = 'storefront.builder.';

    /** @var array<string, BuilderSurface>|null */
    private ?array $surfaces = null;

    /** @var array<string, BlockLayout> */
    private array $layouts = [];

    /** @var array<string, list<array{surface: string, zone: string}>>|null slot => vùng gắn vào slot */
    private ?array $slotZones = null;

    /** @var array<string, list<array{type: string, config: array<string, mixed>, zone?: string}>|null> */
    private array $stored = [];

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly MediaDirectory $media,
        private readonly PageBlocks $blocks,
    ) {}

    /**
     * @return array<string, BuilderSurface> theo thứ tự đăng ký (Core trước)
     */
    public function surfaces(): array
    {
        return $this->surfaces ??= $this->extensions->implementations(BuilderSurface::TAG, BuilderSurface::class, fn (BuilderSurface $surface): string => $surface->key());
    }

    public function surface(string $key): ?BuilderSurface
    {
        return $this->surfaces()[$key] ?? null;
    }

    /**
     * Bố cục của surface. Vùng slot chưa khai báo `builder: true` bị bỏ (ghi log) để block không rơi vào vị trí
     * không hợp lệ như `<head>` hay form.
     */
    public function layout(BuilderSurface $surface): BlockLayout
    {
        return $this->layouts[$surface->key()] ??= BlockLayout::of(array_values(array_filter(
            $this->extensions->call($surface, fn (): array => $surface->zones(), [], 'storefront.builder_surface'),
            function (BuilderZone $zone) use ($surface): bool {
                if ($zone->slot === null || Hook::acceptsBlocks($zone->slot)) {
                    return true;
                }
                Log::warning('Surface gắn vào slot không nhận block, bỏ vùng.', ['surface' => $surface->key(), 'slot' => $zone->slot]);

                return false;
            },
        )));
    }

    /**
     * @return list<array{type: string, config: array<string, mixed>, zone?: string}>|null null = chưa cấu hình
     */
    public function blocks(string $surface): ?array
    {
        if (! array_key_exists($surface, $this->stored)) {
            $blocks = $this->settings->get('core', self::SETTING_PREFIX.$surface);
            $this->stored[$surface] = is_array($blocks) ? array_values($blocks) : null;
        }

        return $this->stored[$surface];
    }

    /**
     * Block của một vùng; null khi surface chưa cấu hình.
     *
     * @return list<array{type: string, config: array<string, mixed>}>|null
     */
    public function zone(string $surface, string $zone): ?array
    {
        $definition = $this->surface($surface);
        $blocks = $this->blocks($surface);
        if ($definition === null || $blocks === null) {
            return null;
        }

        return $this->layout($definition)->group($blocks)[$zone] ?? [];
    }

    /**
     * @param  list<array{type?: mixed, config?: mixed, zone?: mixed}>  $blocks
     * @return list<array{type: string, config: array<string, mixed>, zone?: string}>
     */
    public function save(string $surface, array $blocks): array
    {
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $this->blocks->validate($blocks, $this->layout($this->require($surface)));
        $this->settings->set('core', self::SETTING_PREFIX.$surface, $clean);
        $this->media->syncUsages(self::MEDIA_OWNER, 1, $surface, $mediaIds);
        $this->stored[$surface] = $clean;

        return $clean;
    }

    public function reset(string $surface): void
    {
        $this->require($surface);
        $this->settings->forget('core', self::SETTING_PREFIX.$surface);
        $this->media->syncUsages(self::MEDIA_OWNER, 1, $surface, []);
        $this->stored[$surface] = null;
    }

    /**
     * HTML các block admin đặt vào slot, gộp từ mọi surface gắn vào slot đó. Slot không có vùng nào → không đọc cấu hình.
     */
    public function renderSlot(string $slot): HtmlString
    {
        $html = '';
        foreach ($this->slotZones()[$slot] ?? [] as ['surface' => $surface, 'zone' => $zone]) {
            foreach ($this->blocks->render($this->zone($surface, $zone) ?? []) as $block) {
                $html .= $block['html']->toHtml();
            }
        }

        return new HtmlString($html);
    }

    private function require(string $surface): BuilderSurface
    {
        return $this->surface($surface) ?? throw new InvalidArgumentException("Surface Builder Engine không tồn tại hoặc plugin đã tắt: {$surface}.");
    }

    /** @return array<string, list<array{surface: string, zone: string}>> */
    private function slotZones(): array
    {
        if ($this->slotZones === null) {
            $this->slotZones = [];
            foreach ($this->surfaces() as $key => $surface) {
                foreach ($this->layout($surface)->zones as $zone) {
                    if ($zone->slot !== null) {
                        $this->slotZones[$zone->slot][] = ['surface' => $key, 'zone' => $zone->key];
                    }
                }
            }
        }

        return $this->slotZones;
    }
}
