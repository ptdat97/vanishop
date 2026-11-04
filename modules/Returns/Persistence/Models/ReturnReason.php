<?php

declare(strict_types=1);

namespace Modules\Returns\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $code
 * @property array<string, string> $names ngôn ngữ => tên
 * @property int $position
 * @property bool $is_active
 * @property bool $store_fault
 */
final class ReturnReason extends Model
{
    protected $fillable = ['code', 'names', 'position', 'is_active', 'store_fault'];

    protected function casts(): array
    {
        return ['names' => 'array', 'position' => 'integer', 'is_active' => 'boolean', 'store_fault' => 'boolean'];
    }
}
