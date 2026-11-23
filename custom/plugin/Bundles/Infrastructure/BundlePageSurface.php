<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Modules\Storefront\Contracts\BuilderSurface;
use Modules\Storefront\Contracts\Data\BuilderZone;

final class BundlePageSurface implements BuilderSurface
{
    public const KEY = 'bundles';

    public const BEFORE_LIST = 'vani.bundles.page.before_list';

    public const AFTER_LIST = 'vani.bundles.page.after_list';

    public function key(): string
    {
        return self::KEY;
    }

    public function label(): string
    {
        return __('vani-bundles::texts.page_title');
    }

    public function zones(): array
    {
        return [
            BuilderZone::slot(self::BEFORE_LIST, __('vani-bundles::texts.before_list')),
            BuilderZone::slot(self::AFTER_LIST, __('vani-bundles::texts.after_list')),
        ];
    }
}
