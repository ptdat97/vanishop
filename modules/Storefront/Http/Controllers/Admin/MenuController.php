<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Application\Menus\MenuRepository;
use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\LinkResolver;

/**
 * Admin → Giao diện → Menu: cây menu theo vị trí theme khai báo; sửa nháp, xuất bản, bỏ nháp, khôi phục bản trước.
 */
final class MenuController
{
    public function edit(Request $request, MenuRepository $menus, LinkResolver $links, MediaDirectory $media): Response
    {
        Gate::authorize('storefront.manage');
        $locations = $menus->locations();
        $location = (string) $request->query('location', (string) array_key_first($locations));
        $state = isset($locations[$location]) ? $menus->state($location) : null;

        $targets = [];
        $mediaIds = [];
        $walk = function (array $items) use (&$walk, &$targets, &$mediaIds): void {
            foreach ($items as $item) {
                if (is_array($item['link'] ?? null) && ($target = LinkTarget::fromArray($item['link'])) !== null) {
                    $targets[$item['id']] = $target;
                }
                if (is_int($item['image_media_id'] ?? null)) {
                    $mediaIds[] = $item['image_media_id'];
                }
                $walk((array) ($item['children'] ?? []));
            }
        };
        $walk($state['draft'] ?? []);
        $locale = StoreLocale::default();

        return Inertia::render('Storefront::Design/Menus', [
            'baseUrl' => route('admin.storefront.menus.edit'),
            'searchUrl' => route('admin.storefront.menus.links'),
            'locations' => array_map(fn (string $code, array $info): array => ['code' => $code, ...$info], array_keys($locations), $locations),
            'location' => isset($locations[$location]) ? $location : null,
            'menu' => $state === null ? null : [
                'items' => $state['draft'],
                'published_at' => $state['published_at'],
                'has_changes' => $state['published'] === null || $state['draft'] !== $state['published'],
                'versions' => array_map(fn (array $version): array => ['published_at' => $version['published_at'] ?? null, 'count' => count($version['items'])], $state['versions']),
            ],
            'linkTypes' => $links->types(),
            // Nhãn hiện tại của link đã chọn (theo ngôn ngữ mặc định) và ảnh — để trình sửa hiển thị đúng đối tượng.
            'linkLabels' => array_map(fn ($resolved): array => ['label' => $resolved->label, 'exists' => $resolved->exists], $links->resolveMany($targets, $locale)),
            'images' => array_map(fn ($image): string => $image->urlFor(160), $media->find(array_values(array_unique($mediaIds)))),
        ]);
    }

    public function update(Request $request, string $location, MenuRepository $menus): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $data = $request->validate(['items' => ['present', 'array']]);
        $menus->saveDraft($location, $data['items']);

        return back()->with('success', __('storefront::messages.menu_saved'));
    }

    public function publish(string $location, MenuRepository $menus, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $menus->publish($location);
        $audit->record('storefront.menu_published', 'storefront_menu', $location);

        return back()->with('success', __('storefront::messages.menu_published'));
    }

    public function discard(string $location, MenuRepository $menus): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $menus->discardDraft($location);

        return back()->with('success', __('storefront::messages.menu_discarded'));
    }

    public function restore(string $location, int $version, MenuRepository $menus, AuditLogger $audit): RedirectResponse
    {
        Gate::authorize('storefront.manage');
        $menus->restore($location, $version);
        $audit->record('storefront.menu_restored', 'storefront_menu', $location, ['version' => $version]);

        return back()->with('success', __('storefront::messages.menu_restored'));
    }

    /**
     * Tìm đối tượng cho link (danh mục, sản phẩm, trang của plugin…) — JSON cho bộ chọn link.
     */
    public function links(Request $request, LinkResolver $links): JsonResponse
    {
        Gate::authorize('storefront.manage');
        $data = $request->validate(['type' => ['required', 'string', 'max:64'], 'q' => ['nullable', 'string', 'max:100']]);

        return response()->json(['data' => $links->search($data['type'], (string) ($data['q'] ?? ''), StoreLocale::default(), 20)]);
    }
}
