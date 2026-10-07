<?php

declare(strict_types=1);

namespace Modules\Extension\Domain\Plugin;

enum PluginStatus: string
{
    case Installed = 'installed';
    case Enabled = 'enabled';
    /**
     * Đang ngừng (0.3.23): vẫn chạy cho giao dịch đang dở (IPN, webhook, tra cứu, hoàn tiền, hook) nhưng không được chọn
     * cho giao dịch mới; tự chuyển sang disabled khi hết việc dở dang (vani:plugin:finish-draining).
     */
    case Draining = 'draining';
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
