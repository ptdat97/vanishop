<?php

declare(strict_types=1);

namespace Modules\Identity\Application;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;

final class DatabaseAuditLogger implements AuditLogger
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly Request $request,
    ) {}

    public function record(string $action, ?string $subjectType = null, int|string|null $subjectId = null, array $changes = []): void
    {
        $actor = $this->context->has() ? $this->context->actor() : null;

        AuditLog::query()->create([
            'actor_type' => $actor?->type->value ?? ActorType::System->value,
            'actor_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId === null ? null : (string) $subjectId,
            'changes' => $changes === [] ? null : $changes,
            'ip_address' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 512) ?: null,
            'correlation_id' => Context::get('correlation_id'),
            'created_at' => now(),
        ]);
    }
}
