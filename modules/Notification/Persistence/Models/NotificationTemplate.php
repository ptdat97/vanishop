<?php

declare(strict_types=1);

namespace Modules\Notification\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
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
    protected $fillable = ['type', 'channel', 'locale', 'subject', 'body', 'meta', 'active', 'lock_version'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'active' => 'boolean', 'lock_version' => 'integer'];
    }
}
