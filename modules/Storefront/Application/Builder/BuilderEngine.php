<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Builder;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Facades\Hook;
use Modules\Storefront\Application\Blocks\BlockLayout;
use Modules\Storefront\Application\PageBlocks;
use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BlockContext;
use Modules\Storefront\Contracts\Data\BuilderZone;
use Modules\Storefront\Contracts\ScopedBuilderSurface;
use Modules\Storefront\Persistence\Models\ScopedBuilderBlocks;
use Modules\Tenancy\Contracts\Settings;

/**
 * Builder Engine: quản lý các {@see BuilderSurface} (Core + plugin), lưu cấu hình block theo surface và render block
 * của vùng slot tại `<x-vani::hook-slot>`. Schema, validation và renderer block dùng chung {@see PageBlocks}.
 * Scoped theo request: danh sách surface, chỉ mục slot và cấu hình đã đọc được nhớ trong request.
 *
 * Surface theo phạm vi ({@see ScopedBuilderSurface}) lưu block theo (surface, phạm vi) ở bảng `builder_scoped_blocks`;
 * surface thường lưu ở Settings. Tại một slot, block riêng của phạm vi trang đang xem thay block chung của slot đó.
 */
final class BuilderEngine
{
    public const HOME = 'home';

    /** Chủ sở hữu ghi nhận ảnh đang dùng (Thư viện ảnh không cho xoá); role = mã surface. */
    public const MEDIA_OWNER = 'storefront.builder';

    /** Chủ sở hữu ghi nhận ảnh của block theo phạm vi; id = dòng `builder_scoped_blocks`. */
    public const SCOPED_MEDIA_OWNER = 'storefront.builder_scoped';

    /** Slot danh sách sản phẩm nhận ngữ cảnh trang (loại, slug, id đối tượng). */
    private const LISTING_SLOTS = ['vani.storefront.plp.header', 'vani.storefront.plp.before_listing', 'vani.storefront.plp.after_listing', 'vani.storefront.plp.empty'];

    private const SETTING_PREFIX = 'storefront.builder.';

    /** @var array<string, BuilderSurface>|null */
    private ?array $surfaces = null;

    /** @var array<string, BlockLayout> */
    private array $layouts = [];

    /** @var array<string, list<array{surface: string, zone: string}>>|null slot => vùng gắn vào slot */
    private ?array $slotZones = null;

    /** @var array<string, list<array{type: string, config: array<string, mixed>, zone?: string}>|null> khoá: surface hoặc "surface|phạm vi" */
    private array $stored = [];

    public function __construct(
        private readonly Extensions $extensions,
        private readonly Settings $settings,
        private readonly MediaDirectory $media,
        private readonly PageBlocks $blocks,
    ) {}

    /**
     * @return array<string, BuilderSurface> surface hệ thống trước, surface của plugin sau ({@see SystemFirst})
     */
    public function surfaces(): array
    {
        return $this->surfaces ??= SystemFirst::order(
            $this->extensions->implementations(BuilderSurface::TAG, BuilderSurface::class, fn (BuilderSurface $surface): string => $surface->key()),
            $this->extensions,
        );
    }

    /** Id plugin khai báo surface; null = hệ thống. */
    public function ownerOf(BuilderSurface $surface): ?string
    {
        return $this->extensions->ownerOf($surface);
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

    public static function isValidScope(string $scope): bool
    {
        return preg_match('/^'.ScopedBuilderSurface::SCOPE_PATTERN.'$/', $scope) === 1;
    }

    /**
     * @param  string|null  $scope  phạm vi của surface {@see ScopedBuilderSurface}; null với surface thường
     * @return list<array{type: string, config: array<string, mixed>, zone?: string}>|null null = chưa cấu hình
     */
    public function blocks(string $surface, ?string $scope = null): ?array
    {
        $key = $scope === null ? $surface : "{$surface}|{$scope}";
        if (! array_key_exists($key, $this->stored)) {
            $blocks = $scope === null
                ? $this->settings->get('core', self::SETTING_PREFIX.$surface)
                : ScopedBuilderBlocks::query()->where(['surface' => $surface, 'scope' => $scope])->value('blocks');
            $this->stored[$key] = is_array($blocks) ? array_values($blocks) : null;
        }

        return $this->stored[$key];
    }

    /**
     * Block của một vùng; null khi surface (hoặc phạm vi) chưa cấu hình.
     *
     * @return list<array{type: string, config: array<string, mixed>}>|null
     */
    public function zone(string $surface, string $zone, ?string $scope = null): ?array
    {
        $definition = $this->surface($surface);
        $blocks = $this->blocks($surface, $scope);
        if ($definition === null || $blocks === null) {
            return null;
        }

        return $this->layout($definition)->group($blocks)[$zone] ?? [];
    }

    /**
     * @param  list<array{type?: mixed, config?: mixed, zone?: mixed}>  $blocks
     * @return list<array{type: string, config: array<string, mixed>, zone?: string}>
     */
    public function save(string $surface, array $blocks, ?string $scope = null): array
    {
        $definition = $this->require($surface, $scope);
        ['blocks' => $clean, 'media_ids' => $mediaIds] = $this->blocks->validate($blocks, $this->layout($definition));
        if ($scope === null) {
            $this->settings->set('core', self::SETTING_PREFIX.$surface, $clean);
            $this->media->syncUsages(self::MEDIA_OWNER, 1, $surface, $mediaIds);
            $this->stored[$surface] = $clean;

            return $clean;
        }
        DB::transaction(function () use ($surface, $scope, $clean, $mediaIds): void {
            $row = ScopedBuilderBlocks::query()->updateOrCreate(['surface' => $surface, 'scope' => $scope], ['blocks' => $clean]);
            $this->media->syncUsages(self::SCOPED_MEDIA_OWNER, $row->id, 'blocks', $mediaIds);
        });
        $this->stored["{$surface}|{$scope}"] = $clean;

        return $clean;
    }

    public function reset(string $surface, ?string $scope = null): void
    {
        $this->require($surface, $scope);
        if ($scope === null) {
            $this->settings->forget('core', self::SETTING_PREFIX.$surface);
            $this->media->syncUsages(self::MEDIA_OWNER, 1, $surface, []);
            $this->stored[$surface] = null;

            return;
        }
        DB::transaction(function () use ($surface, $scope): void {
            $row = ScopedBuilderBlocks::query()->where(['surface' => $surface, 'scope' => $scope])->lockForUpdate()->first();
            if ($row !== null) {
                $this->media->releaseUsages(self::SCOPED_MEDIA_OWNER, $row->id);
                $row->delete();
            }
        });
        $this->stored["{$surface}|{$scope}"] = null;
    }

    /**
     * Phạm vi đã có nội dung riêng của một surface (để plugin liệt kê đối tượng đã tuỳ biến).
     *
     * @return list<string>
     */
    public function configuredScopes(string $surface): array
    {
        return ScopedBuilderBlocks::query()->where('surface', $surface)->orderBy('scope')->pluck('scope')->all();
    }

    /**
     * HTML các block admin đặt vào slot, gộp từ mọi surface gắn vào slot đó. Slot không có vùng nào → không đọc cấu hình.
     */
    /** @param list<mixed> $args */
    public function renderSlot(string $slot, array $args = []): HtmlString
    {
        if (! isset($this->slotZones()[$slot])) {
            return new HtmlString('');
        }
        $context = $this->slotContext($slot, $args);
        $blocks = $this->slotBlocks($slot, $context)['blocks'];
        if ($blocks === []) {
            return new HtmlString('');
        }
        $html = '';
        foreach ($this->blocks->render($blocks, $this->slotViewData($slot, $args), $context) as $block) {
            $html .= $block['html']->toHtml();
        }

        return new HtmlString($html);
    }

    /**
     * @param  array<string, mixed>  $publicContext
     * @return array{configured: bool, zones: list<array{key: string, blocks: list<array{type: string, data: array<string, mixed>}>}>}
     */
    public function resolveLayout(string $surface, array $publicContext): array
    {
        $zones = [];
        $configured = $this->blocks($surface) !== null;
        foreach ($this->layout($this->require($surface))->zones as $zone) {
            if ($zone->slot === null) {
                $resolved = $this->blocks->resolve($this->zone($surface, $zone->key) ?? []);
            } else {
                $context = $this->slotContext($zone->slot, [$publicContext]);
                $slot = $this->slotBlocks($zone->slot, $context);
                $configured = $configured || $slot['configured'];
                $resolved = $this->blocks->resolve($slot['blocks'], $context);
            }
            $zones[] = ['key' => $zone->key, 'blocks' => $resolved];
        }

        return ['configured' => $configured, 'zones' => $zones];
    }

    /**
     * Block của slot gộp từ mọi surface gắn vào slot. Có block riêng của phạm vi trang đang xem → thay block chung.
     *
     * @return array{configured: bool, blocks: list<array{type: string, config: array<string, mixed>}>}
     */
    private function slotBlocks(string $slot, BlockContext $context): array
    {
        $shared = [];
        $scoped = [];
        $configured = false;
        foreach ($this->slotZones()[$slot] ?? [] as ['surface' => $surface, 'zone' => $zone]) {
            $definition = $this->surface($surface);
            $scope = null;
            if ($definition instanceof ScopedBuilderSurface) {
                $scope = $this->extensions->call($definition, fn (): ?string => $definition->scopeOf($context), null, 'storefront.builder_scope');
                if (! is_string($scope) || ! self::isValidScope($scope)) {
                    continue;
                }
            }
            $stored = $this->zone($surface, $zone, $scope);
            if ($stored !== null) {
                $configured = true;
                if ($scope === null) {
                    array_push($shared, ...$stored);
                } else {
                    array_push($scoped, ...$stored);
                }
            }
        }

        return ['configured' => $configured, 'blocks' => $scoped !== [] ? $scoped : $shared];
    }

    /** @param list<mixed> $args */
    private function slotContext(string $slot, array $args): BlockContext
    {
        $public = is_array($args[0] ?? null) ? $args[0] : [];
        if (str_starts_with($slot, 'vani.storefront.pdp.')) {
            $id = $public['id'] ?? null;

            return new BlockContext($slot, productId: is_int($id) && $id > 0 ? $id : null);
        }
        if (in_array($slot, self::LISTING_SLOTS, true)) {
            $type = $public['type'] ?? null;
            if (in_array($type, ['brand', 'category', 'collection', 'search'], true)) {
                $slug = $public[$type]['slug'] ?? null;
                $id = $public[$type]['id'] ?? null;

                return new BlockContext($slot, listingType: $type, listingSlug: is_string($slug) ? $slug : null, listingId: is_int($id) && $id > 0 ? $id : null);
            }
        }

        return new BlockContext($slot);
    }

    /**
     * Surface phạm vi bắt buộc có phạm vi hợp lệ; surface thường không nhận phạm vi.
     */
    /**
     * Biến thêm cho view của block theo slot. "Đầu trang" danh sách: `pageHeader` (loại + dữ liệu công khai của trang)
     * để khối như Hero hiện thành phần đầu trang có h1, chữ/ảnh để trống thì lấy của trang.
     *
     * @param  list<mixed>  $args
     * @return array<string, mixed>
     */
    private function slotViewData(string $slot, array $args): array
    {
        $public = is_array($args[0] ?? null) ? $args[0] : [];
        $type = $public['type'] ?? null;
        if ($slot !== 'vani.storefront.plp.header' || ! is_string($type) || ! is_array($public[$type] ?? null)) {
            return [];
        }

        return ['pageHeader' => ['type' => $type, ...$public[$type], 'name' => (string) ($public[$type]['name'] ?? '')]];
    }

    private function require(string $surface, ?string $scope = null): BuilderSurface
    {
        $definition = $this->surface($surface) ?? throw new InvalidArgumentException("Surface Builder Engine không tồn tại hoặc plugin đã tắt: {$surface}.");
        $scoped = $definition instanceof ScopedBuilderSurface;
        if ($scoped !== ($scope !== null) || ($scope !== null && ! self::isValidScope($scope))) {
            throw new InvalidArgumentException("Phạm vi không hợp lệ cho surface {$surface}.");
        }

        return $definition;
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
