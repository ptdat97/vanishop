<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Media\ImageCache;
use Modules\Catalog\Application\Media\ImageSizes;
use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Admin → Thư viện ảnh → Kích thước ảnh (0.3.87): bề rộng chung, tỉ lệ ảnh sản phẩm (mặc định 2:3), chất lượng, và kích
 * thước có tên của plugin (ghi đè rộng/cao/cắt khung). Đổi kích thước đổi luôn URL ảnh (kích thước nằm trong tên file);
 * bản cũ nằm lại trên đĩa đến khi "Xoá cache ảnh".
 */
final class ImageSizeController
{
    public function __construct(
        private readonly ImageSizes $sizes,
        private readonly AuditLogger $audit,
    ) {}

    public function index(): Response
    {
        Gate::authorize('media.view');

        return Inertia::render('Catalog::Media/Sizes', [
            'widths' => $this->sizes->widths(),
            'productRatio' => $this->sizes->ratioCode(),
            'ratios' => array_map(fn (string $value, string $label): array => ['value' => $value, 'label' => $label], array_keys(ImageSizes::RATIOS), ImageSizes::RATIOS),
            'quality' => $this->sizes->quality(),
            'productSizes' => array_map(fn (ImageSize $size): array => $size->toArray(), $this->sizes->responsive(ImageSizes::PRODUCT)),
            'named' => $this->sizes->catalog(),
            'limits' => ['min' => ImageSize::MIN, 'max' => ImageSize::MAX, 'widths' => ImageSizes::MAX_WIDTHS],
            'urls' => ['save' => route('admin.media.sizes.update'), 'flush' => route('admin.media.sizes.flush'), 'library' => route('admin.media.index')],
            'canManage' => Gate::allows('media.manage'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        Gate::authorize('media.manage');
        $range = 'between:'.ImageSize::MIN.','.ImageSize::MAX;
        $data = $request->validate([
            'widths' => ['required', 'array', 'min:1', 'max:'.ImageSizes::MAX_WIDTHS],
            'widths.*' => ['integer', $range],
            'product_ratio' => ['required', 'string', 'in:'.implode(',', array_keys(ImageSizes::RATIOS))],
            'quality' => ['required', 'integer', 'between:30,100'],
            'overrides' => ['nullable', 'array'],
            'overrides.*' => ['nullable', 'array'],
            'overrides.*.width' => ['required_with:overrides.*', 'integer', $range],
            'overrides.*.height' => ['nullable', 'integer', $range],
            'overrides.*.crop' => ['boolean'],
        ]);
        $this->sizes->save($data);
        $this->audit->record('media.sizes.updated', 'settings', null, ['widths' => $this->sizes->widths(), 'product_ratio' => $this->sizes->ratioCode(), 'quality' => $this->sizes->quality()]);

        return back()->with('success', 'Đã lưu kích thước ảnh. Ảnh mới được tạo dần khi có người xem.');
    }

    public function flush(ImageCache $cache): RedirectResponse
    {
        Gate::authorize('media.manage');
        $cache->flush();
        $this->audit->record('media.cache.flushed', 'settings', null, []);

        return back()->with('success', 'Đã xoá cache ảnh; bản thu nhỏ được tạo lại khi có người xem.');
    }
}
