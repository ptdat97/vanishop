<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts\Data;

/**
 * Một kho/cửa hàng (0.3.88, ADR-042) cho plugin vận hành kho (`vani.warehouse`) và module khác — chỉ đọc.
 */
final readonly class LocationData
{
    public const AUTHORITY_VANISHOP = 'vanishop';

    public const TYPES = ['warehouse', 'store', 'virtual'];

    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        /** warehouse | store | virtual */
        public string $type,
        public ?string $address,
        public ?string $provinceCode,
        public bool $shipsOnlineOrders,
        public bool $allowsPickup,
        public bool $acceptsReturns,
        /** `vanishop` = VaniShop quản lý tồn vật lý; khác = mã hệ thống ngoài (ERP/POS) — chỉ nhận đồng bộ. */
        public string $stockAuthority,
        public int $priority,
        /** active | inactive */
        public string $status,
        public int $lockVersion,
    ) {}

    public function managesOnHand(): bool
    {
        return $this->stockAuthority === self::AUTHORITY_VANISHOP;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name, 'type' => $this->type, 'address' => $this->address,
            'province_code' => $this->provinceCode, 'ships_online_orders' => $this->shipsOnlineOrders, 'allows_pickup' => $this->allowsPickup,
            'accepts_returns' => $this->acceptsReturns, 'stock_authority' => $this->stockAuthority, 'priority' => $this->priority,
            'status' => $this->status, 'lock_version' => $this->lockVersion,
        ];
    }
}
