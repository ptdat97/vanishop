<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Modules\Catalog\Contracts\Data\ImageSize;
use Modules\Catalog\Contracts\ImageSizeProvider;

final class CmsImageSizes implements ImageSizeProvider
{
    public const BANNER_DESKTOP = 'cms.banner_desktop';

    public const BANNER_MOBILE = 'cms.banner_mobile';

    public const POST_COVER = 'cms.post_cover';

    public function sizes(): array
    {
        return [
            new ImageSize(self::BANNER_DESKTOP, __('vani-cms::texts.image_sizes.banner_desktop'), 1600),
            new ImageSize(self::BANNER_MOBILE, __('vani-cms::texts.image_sizes.banner_mobile'), 800),
            new ImageSize(self::POST_COVER, __('vani-cms::texts.image_sizes.post_cover'), 1600),
        ];
    }
}
