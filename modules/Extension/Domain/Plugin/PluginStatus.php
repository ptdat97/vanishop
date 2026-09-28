<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

enum PluginStatus: string
{
    case Installed = 'installed';
    case Enabled = 'enabled';
    case Disabled = 'disabled';
    case Failed = 'failed';

    /**
     * Trạng thái mà ServiceProvider của plugin được nạp.
     */
    public function loadsProvider(): bool
    {
        return $this !== self::Failed;
    }
}
