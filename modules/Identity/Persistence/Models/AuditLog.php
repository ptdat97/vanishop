<?php

declare(strict_types=1);

namespace Modules\Identity\Persistence\Models;

use Illuminate\Database\Eloquent\Model;
use LogicException;

/**
 * Nhật ký audit — append-only: không cho phép cập nhật hoặc xoá.
 */
final class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'actor_type', 'actor_id', 'action', 'subject_type', 'subject_id',
        'changes', 'ip_address', 'user_agent', 'correlation_id', 'created_at',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
            'created_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        self::updating(fn () => throw new LogicException('audit_logs là append-only.'));
        self::deleting(fn () => throw new LogicException('audit_logs là append-only.'));
    }
}
