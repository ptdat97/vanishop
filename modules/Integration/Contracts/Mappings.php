<?php

declare(strict_types=1);

namespace Modules\Integration\Contracts;

/**
 * Service contract: danh mục mã giữa VaniShop và hệ thống ngoài (mã kho, phương thức thanh toán, trạng thái…).
 * Connector gặp mapping thiếu nên trả `DeliveryResult::permanent((string) $exception)`: message chuyển
 * `failed`, Admin bổ sung mapping rồi replay.
 */
interface Mappings
{
    /**
     * @throws MappingMissing
     */
    public function map(string $system, string $type, string $internalValue): string;

    public function put(string $system, string $type, string $internalValue, string $externalValue): void;
}
