<?php

declare(strict_types=1);

namespace Modules\Catalog\Listeners;

use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Events\SettingChanged;

/**
 * Cấu hình kiểu `media` (Ảnh thay thế, Ảnh khi chia sẻ link, cấu hình `media` của plugin…) đổi → khai báo ảnh đang chọn
 * là "đang dùng" trong Thư viện ảnh (không xoá được), bỏ khai báo ảnh cũ (backlog L22). Mỗi cấu hình một chủ sở hữu
 * `setting` (id = crc32 của `namespace.key`, vai trò = khoá cấu hình).
 */
final class DeclareSettingMedia
{
    public const OWNER = 'setting';

    public function __construct(
        private readonly Settings $settings,
        private readonly MediaDirectory $media,
    ) {}

    public function handle(SettingChanged $event): void
    {
        $definition = null;
        foreach ($this->settings->definitions($event->namespace) as $candidate) {
            if ($candidate->key === $event->key) {
                $definition = $candidate;
            }
        }
        if ($definition?->type !== 'media') {
            return;
        }

        $mediaId = filter_var($event->value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $this->media->syncUsages(self::OWNER, self::ownerId($event->namespace, $event->key), mb_substr($event->key, -32), $mediaId === false ? [] : [$mediaId]);
    }

    public static function ownerId(string $namespace, string $key): int
    {
        return crc32("{$namespace}.{$key}");
    }
}
