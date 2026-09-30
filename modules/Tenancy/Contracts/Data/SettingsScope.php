<?php

declare(strict_types=1);

namespace Modules\Tenancy\Contracts\Data;

use Modules\Shared\Context\ContextScope;

/**
 * Phạm vi đọc cấu hình. Giá trị được tìm theo thứ tự kênh → brand → pháp nhân → owner → mặc định; phạm vi
 * nào không biết (null) thì bỏ qua.
 */
final readonly class SettingsScope
{
    public const OWNER = 'owner';

    public const LEGAL_ENTITY = 'legal_entity';

    public const BRAND = 'brand';

    public const CHANNEL = 'channel';

    public function __construct(
        public ?int $channelId = null,
        public ?int $brandId = null,
        public ?int $legalEntityId = null,
    ) {}

    public static function owner(): self
    {
        return new self;
    }

    public static function brand(int $brandId, ?int $legalEntityId = null): self
    {
        return new self(brandId: $brandId, legalEntityId: $legalEntityId);
    }

    /**
     * Phạm vi của request/job hiện tại: kênh + brand (khi phạm vi chỉ có một brand).
     */
    public static function fromContext(?ContextScope $scope, ?int $channelId = null): self
    {
        $brandIds = $scope?->brandIds;

        return new self($channelId ?? $scope?->channelId, $brandIds !== null && count($brandIds) === 1 ? $brandIds[0] : null);
    }

    /**
     * Chuỗi phạm vi để tra, cụ thể nhất trước.
     *
     * @return list<array{0: string, 1: int}>
     */
    public function chain(): array
    {
        return array_values(array_filter([
            $this->channelId === null ? null : [self::CHANNEL, $this->channelId],
            $this->brandId === null ? null : [self::BRAND, $this->brandId],
            $this->legalEntityId === null ? null : [self::LEGAL_ENTITY, $this->legalEntityId],
            [self::OWNER, 0],
        ]));
    }
}
