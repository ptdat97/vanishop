<?php

declare(strict_types=1);

namespace Plugin\Bundles\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\CatalogLookup;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Shared\Support\StoreClock;
use Modules\Shared\Support\StoreLocale;
use Plugin\Bundles\Application\Bundles;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Domain\BundleConfig;
use Plugin\Bundles\Persistence\Bundle;
use Plugin\Bundles\Persistence\BundleItem;

/**
 * Admin → Sản phẩm → Combo: danh sách, tạo/sửa/xoá combo. Lưu combo cũng cập nhật khuyến mãi tương ứng ở Khuyến mãi.
 */
final class BundleController
{
    public function __construct(
        private readonly Bundles $bundles,
        private readonly CatalogLookup $catalog,
        private readonly MediaDirectory $media,
        private readonly VariantDirectory $variants,
    ) {}

    public function index(ManagedPromotions $promotions): Response
    {
        Gate::authorize('bundles.view');
        $now = now()->getTimestamp();
        $promotionIds = Gate::allows('promotion.view') ? $promotions->ids(BundlesServiceProvider::ID) : [];
        $locale = StoreLocale::default();

        return Inertia::render('Bundles::Index', [
            'bundles' => Bundle::query()->withCount('items')->orderBy('position')->orderByDesc('id')->get()->map(fn (Bundle $bundle): array => [
                'id' => $bundle->id,
                'name' => $bundle->text('name', $locale),
                'slug' => $bundle->slug,
                'items_count' => $bundle->items_count,
                'discount_percent' => intdiv($bundle->discount_basis_points, 100),
                'active' => $bundle->active,
                'running' => $bundle->isRunningAt($now),
                'period' => array_values(array_filter([StoreClock::format($bundle->starts_at), StoreClock::format($bundle->ends_at)])),
                'edit_url' => route('admin.plugins.vani-bundles.edit', $bundle->id),
                'promotion_url' => isset($promotionIds[(string) $bundle->id]) ? route('admin.promotion.promotions.edit', $promotionIds[(string) $bundle->id]) : null,
            ])->all(),
            'bundleUrls' => [
                'create' => route('admin.plugins.vani-bundles.create'),
                'storefront' => vani_route('storefront.p.vani-bundles.index'),
            ],
            'can' => ['manage' => Gate::allows('bundles.manage')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('bundles.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('bundles.manage');
        $bundle = $this->bundles->save($this->validated($request));

        return redirect()->route('admin.plugins.vani-bundles.edit', $bundle->id)->with('success', __('vani-bundles::texts.saved'));
    }

    public function edit(Bundle $bundle): Response
    {
        Gate::authorize('bundles.view');

        return $this->form($bundle->load('items'));
    }

    public function update(Request $request, Bundle $bundle): RedirectResponse
    {
        Gate::authorize('bundles.manage');
        $this->bundles->save($this->validated($request, $bundle), $bundle);

        return back()->with('success', __('vani-bundles::texts.saved'));
    }

    public function destroy(Bundle $bundle): RedirectResponse
    {
        Gate::authorize('bundles.manage');
        $this->bundles->delete($bundle);

        return redirect()->route('admin.plugins.vani-bundles.index')->with('success', __('vani-bundles::texts.deleted'));
    }

    public function products(Request $request): JsonResponse
    {
        Gate::authorize('bundles.manage');
        $validated = $request->validate(['q' => ['nullable', 'string', 'max:120']]);

        return response()->json(['data' => array_map(fn (array $product): array => [
            'value' => (string) $product['id'],
            'label' => $product['name'].($product['visible'] ? '' : __('vani-bundles::texts.hidden')),
        ], $this->catalog->search('product', $validated['q'] ?? '', StoreLocale::default()))]);
    }

    private function form(?Bundle $bundle): Response
    {
        $locale = StoreLocale::default();
        $styleIds = $bundle?->items->pluck('style_id')->all() ?? [];
        $visible = $this->catalog->visible('product', $styleIds, $locale);

        return Inertia::render('Bundles::Form', [
            'bundle' => $bundle === null ? null : [
                'id' => $bundle->id,
                'slug' => $bundle->slug,
                'name' => $bundle->text('name', $locale),
                'description' => $bundle->text('description', $locale),
                'image_media_id' => $bundle->image_media_id,
                'discount_percent' => intdiv($bundle->discount_basis_points, 100),
                'active' => $bundle->active,
                'starts_at' => StoreClock::toInput($bundle->starts_at),
                'ends_at' => StoreClock::toInput($bundle->ends_at),
                'position' => $bundle->position,
                'items' => $bundle->items->map(fn (BundleItem $item): array => [
                    'style_id' => $item->style_id,
                    'label' => $visible[$item->style_id]['name'] ?? $this->hiddenLabel($item->style_id),
                    'quantity' => $item->quantity,
                ])->all(),
            ],
            'limits' => ['min_items' => BundleConfig::MIN_ITEMS, 'max_items' => BundleConfig::MAX_ITEMS, 'max_quantity' => BundleConfig::MAX_QUANTITY,
                'min_percent' => BundleConfig::MIN_BASIS_POINTS / 100, 'max_percent' => BundleConfig::MAX_BASIS_POINTS / 100],
            'bundleUrls' => [
                'index' => route('admin.plugins.vani-bundles.index'),
                'save' => $bundle === null ? route('admin.plugins.vani-bundles.store') : route('admin.plugins.vani-bundles.update', $bundle->id),
                'delete' => $bundle === null ? null : route('admin.plugins.vani-bundles.destroy', $bundle->id),
                'products' => route('admin.plugins.vani-bundles.products'),
            ],
            'can' => ['manage' => Gate::allows('bundles.manage')],
        ]);
    }

    private function hiddenLabel(int $styleId): string
    {
        return '#'.$styleId.__('vani-bundles::texts.hidden');
    }

    /**
     * @return array{slug: string, name: string, description: ?string, image_media_id: ?int, discount_percent: int, active: bool, starts_at: ?Carbon, ends_at: ?Carbon, position: int, items: list<array{style_id: int, quantity: int}>}
     */
    private function validated(Request $request, ?Bundle $bundle = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['required', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('plg_bundles', 'slug')->ignore($bundle?->id)],
            'description' => ['nullable', 'string', 'max:500'],
            'image_media_id' => ['nullable', 'integer', 'min:1'],
            'discount_percent' => ['required', 'integer', 'min:'.(BundleConfig::MIN_BASIS_POINTS / 100), 'max:'.(BundleConfig::MAX_BASIS_POINTS / 100)],
            'active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date_format:'.StoreClock::INPUT_FORMAT],
            'ends_at' => ['nullable', 'date_format:'.StoreClock::INPUT_FORMAT],
            'position' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'items' => ['required', 'array', 'list', 'min:'.BundleConfig::MIN_ITEMS, 'max:'.BundleConfig::MAX_ITEMS],
            'items.*' => ['required', 'array:style_id,quantity'],
            'items.*.style_id' => ['required', 'integer', 'min:1', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:'.BundleConfig::MAX_QUANTITY],
        ], ['items.min' => __('vani-bundles::texts.min_items', ['count' => BundleConfig::MIN_ITEMS]), 'items.*.style_id.distinct' => __('vani-bundles::texts.distinct_items')]);

        $imageId = isset($data['image_media_id']) ? (int) $data['image_media_id'] : null;
        if ($imageId !== null) {
            $image = $this->media->find([$imageId])[$imageId] ?? null;
            if ($image === null || ! str_starts_with($image->mimeType, 'image/')) {
                throw ValidationException::withMessages(['image_media_id' => __('storefront::texts.page_blocks.choose_image')]);
            }
        }

        $known = $this->variants->ofStyles(array_map(fn (array $item): int => (int) $item['style_id'], $data['items']));
        foreach (array_values($data['items']) as $index => $item) {
            if (! isset($known[(int) $item['style_id']])) {
                throw ValidationException::withMessages(["items.{$index}.style_id" => __('vani-bundles::texts.unknown_product')]);
            }
        }

        return [
            'slug' => $data['slug'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_media_id' => $imageId,
            'discount_percent' => (int) $data['discount_percent'],
            'active' => (bool) $data['active'],
            'starts_at' => empty($data['starts_at']) ? null : StoreClock::fromInput((string) $data['starts_at']),
            'ends_at' => empty($data['ends_at']) ? null : StoreClock::fromInput((string) $data['ends_at']),
            'position' => (int) ($data['position'] ?? 0),
            'items' => array_map(fn (array $item): array => ['style_id' => (int) $item['style_id'], 'quantity' => (int) $item['quantity']], array_values($data['items'])),
        ];
    }
}
