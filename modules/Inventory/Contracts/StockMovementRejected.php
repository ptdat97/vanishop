<?php

declare(strict_types=1);

namespace Modules\Inventory\Contracts;

use RuntimeException;

/**
 * StockMovements từ chối (0.3.88): kho do hệ thống ngoài quản lý, kho không tồn tại, không đủ hàng để xuất. Thông báo
 * dành cho nhân viên.
 */
final class StockMovementRejected extends RuntimeException {}
