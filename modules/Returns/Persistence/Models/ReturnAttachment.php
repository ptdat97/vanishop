<?php

declare(strict_types=1);

namespace Modules\Returns\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $return_request_id
 * @property string $disk
 * @property string $path
 * @property string $mime_type
 * @property int $size_bytes
 * @property string $source
 */
final class ReturnAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];
}
