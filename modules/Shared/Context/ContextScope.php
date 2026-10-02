<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

/**
 * Phạm vi thực thi của request/job hiện tại: ai đang làm (actor) và ngôn ngữ.
 *
 * Một bản cài đặt là một cửa hàng (ADR-028): không có phạm vi brand/kênh.
 */
final readonly class ContextScope
{
    public function __construct(
        public Actor $actor,
        public ?string $locale = null,
    ) {}

    /**
     * Phạm vi cho tác vụ hệ thống (job, CLI, listener) — gọi tường minh, kèm lý do.
     */
    public static function system(string $reason): self
    {
        return new self(Actor::system($reason));
    }
}
