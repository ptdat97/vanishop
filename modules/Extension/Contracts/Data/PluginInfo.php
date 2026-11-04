<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Thông tin chỉ đọc của một plugin (ADR-037, `vani_plugin()`): manifest + trạng thái bật. Không thao tác lifecycle.
 */
final readonly class PluginInfo
{
    public function __construct(
        public string $id,
        /** Tên theo ngôn ngữ hiện hành (rơi về bản không gắn ngôn ngữ / bản đầu tiên). */
        public string $name,
        public string $version,
        public string $kind,
        /** Đang bật hoặc đang ngừng nhận giao dịch mới (draining) — implementation vẫn tham gia flow. */
        public bool $enabled,
        public ?string $author = null,
    ) {}
}
