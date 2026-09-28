<?php

declare(strict_types=1);

namespace Modules\Identity\Contracts;

interface AuditLogger
{
    /**
     * Ghi một dòng audit (append-only). Actor lấy từ CurrentContext nếu có.
     *
     * @param  array<string, mixed>  $changes
     */
    public function record(string $action, ?string $subjectType = null, int|string|null $subjectId = null, array $changes = []): void;
}
