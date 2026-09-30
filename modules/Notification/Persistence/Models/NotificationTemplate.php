<?php

declare(strict_types=1);

namespace Modules\Notification\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int|null $brand_id
 * @property string $type
 * @property string $channel
 * @property string $locale
 * @property string|null $subject
 * @property string|null $body
 * @property array<string, mixed>|null $meta
 * @property bool $active
 * @property int $lock_version
 */
final class NotificationTemplate extends Model
{
    protected $fillable = ['brand_id', 'type', 'channel', 'locale', 'subject', 'body', 'meta', 'active', 'lock_version'];

    protected function casts(): array
    {
        return ['brand_id' => 'integer', 'meta' => 'array', 'active' => 'boolean', 'lock_version' => 'integer'];
    }
}
