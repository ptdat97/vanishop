<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Composers;

use Illuminate\View\View;
use Modules\Catalog\Contracts\MediaUrls;

/**
 * `$productFrame` cho partial có khung ảnh sản phẩm (thẻ sản phẩm, giỏ mini): tỉ lệ cấu hình ở Admin → Thư viện ảnh → Kích
 * thước ảnh ("Giữ tỉ lệ gốc" → 3:4), `aspect` cho CSS `aspect-ratio`, `card` = kích thước ảnh thẻ (rộng 300).
 */
final class ProductFrameComposer
{
    private const CARD_WIDTH = 300;

    public function __construct(private readonly MediaUrls $media) {}

    public function compose(View $view): void
    {
        [$width, $height] = $this->media->productRatio() ?? [3, 4];
        $view->with('productFrame', [
            'aspect' => "{$width} / {$height}",
            'card' => ['width' => self::CARD_WIDTH, 'height' => (int) round(self::CARD_WIDTH * $height / $width)],
        ]);
    }
}
