<?php

declare(strict_types=1);

namespace Modules\Shared\Context;

/**
 * Phạm vi thực thi của request/job hiện tại.
 *
 * $brandIds = null nghĩa là KHÔNG giới hạn brand (nhân viên cấp Owner, hoặc tác vụ hệ thống
 * được khai báo tường minh). Mảng rỗng nghĩa là không được truy cập brand nào.
 */
final readonly class ContextScope
{
    /**
     * @param  list<int>|null  $brandIds
     */
    public function __construct(
        public Actor $actor,
        public ?int $channelId = null,
        public ?array $brandIds = [],
        public ?string $locale = null,
    ) {}

    /**
     * Phạm vi toàn Owner cho tác vụ hệ thống — phải gọi tường minh, kèm lý do.
     */
    public static function system(string $reason): self
    {
        return new self(Actor::system($reason), brandIds: null);
    }

    public function isUnrestricted(): bool
    {
        return $this->brandIds === null;
    }

    public function allowsBrand(int $brandId): bool
    {
        return $this->brandIds === null || in_array($brandId, $this->brandIds, true);
    }
}
