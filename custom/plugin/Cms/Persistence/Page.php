<?php

declare(strict_types=1);

namespace Plugin\Cms\Persistence;

/**
 * @property bool $show_in_header
 * @property bool $show_in_footer
 * @property int $sort_order
 */
final class Page extends Content
{
    protected $table = 'plg_cms_pages';

    protected function casts(): array
    {
        return [...parent::casts(), 'show_in_header' => 'boolean', 'show_in_footer' => 'boolean', 'sort_order' => 'integer'];
    }
}
