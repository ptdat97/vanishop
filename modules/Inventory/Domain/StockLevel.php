<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain;

use InvalidArgumentException;
use LogicException;

/**
 * Tồn của một variant tại một location. Bất biến, thuần PHP — mọi thay đổi trả về đối tượng mới.
 *
 *   available = on_hand − reserved − safety_stock   (có thể âm khi nguồn ngoài hạ on_hand)
 *   ATS       = max(0, available)
 *
 * @see docs/08-inventory/inventory.md
 */
final readonly class StockLevel
{
    public function __construct(
        public int $locationId,
        public int $variantId,
        public int $onHand = 0,
        public int $reserved = 0,
        public int $safetyStock = 0,
        public ?int $syncVersion = null,
    ) {
        if ($reserved < 0 || $safetyStock < 0) {
            throw new InvalidArgumentException('reserved và safety_stock không được âm.');
        }
    }

    public function available(): int
    {
        return $this->onHand - $this->reserved - $this->safetyStock;
    }

    public function ats(): int
    {
        return max(0, $this->available());
    }

    public function reserve(int $quantity): self
    {
        $this->assertPositive($quantity);

        if ($quantity > $this->ats()) {
            throw new InsufficientStock($this->variantId, $quantity, $this->ats());
        }

        return $this->with(reserved: $this->reserved + $quantity);
    }

    public function release(int $quantity): self
    {
        $this->assertPositive($quantity);

        if ($quantity > $this->reserved) {
            throw new LogicException("Giải phóng {$quantity} nhưng chỉ đang giữ {$this->reserved}.");
        }

        return $this->with(reserved: $this->reserved - $quantity);
    }

    /**
     * Xuất kho: bỏ giữ hàng; nếu VaniShop là nguồn gốc on-hand của location thì trừ luôn on_hand.
     * Nếu nguồn gốc là hệ thống ngoài, on_hand giảm khi hệ thống đó gửi số mới (tránh trừ hai lần).
     */
    public function commit(int $quantity, bool $managesOnHand): self
    {
        $released = $this->release($quantity);

        return $managesOnHand ? $released->with(onHand: $released->onHand - $quantity) : $released;
    }

    /**
     * Điều chỉnh tay (+ nhập, − hao hụt). on_hand không được âm do điều chỉnh tay.
     */
    public function adjust(int $delta): self
    {
        if ($delta === 0) {
            throw new InvalidArgumentException('Số điều chỉnh phải khác 0.');
        }
        if ($this->onHand + $delta < 0) {
            throw new InvalidArgumentException("Không thể giảm tồn xuống âm (hiện có {$this->onHand}).");
        }

        return $this->with(onHand: $this->onHand + $delta);
    }

    /**
     * Đặt số tuyệt đối từ nguồn gốc (kiểm kê hoặc đồng bộ). Bản có version cũ hơn → null (bỏ qua).
     */
    public function sync(int $onHand, ?int $version = null): ?self
    {
        if ($onHand < 0) {
            throw new InvalidArgumentException('Tồn vật lý không được âm.');
        }
        if ($version !== null && $this->syncVersion !== null && $version <= $this->syncVersion) {
            return null;
        }

        return $this->with(onHand: $onHand, syncVersion: $version ?? $this->syncVersion);
    }

    public function withSafetyStock(int $safetyStock): self
    {
        return $this->with(safetyStock: $safetyStock);
    }

    private function with(?int $onHand = null, ?int $reserved = null, ?int $safetyStock = null, ?int $syncVersion = null): self
    {
        return new self(
            $this->locationId,
            $this->variantId,
            $onHand ?? $this->onHand,
            $reserved ?? $this->reserved,
            $safetyStock ?? $this->safetyStock,
            $syncVersion ?? $this->syncVersion,
        );
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Số lượng phải lớn hơn 0.');
        }
    }
}
