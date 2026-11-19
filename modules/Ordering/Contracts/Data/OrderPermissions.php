<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

/**
 * Quyền về dữ liệu của khách trong Admin (Ordering đăng ký — module sớm nhất giữ thông tin liên hệ trên đơn).
 */
final class OrderPermissions
{
    /** Xuất danh sách đơn ra Excel (tách khỏi `orders.view`). */
    public const EXPORT = 'orders.export';

    /**
     * Xem thông tin liên hệ của khách: họ tên, SĐT, email, số nhà/đường — trên đơn hàng, khách hàng, file xuất, phiếu giao.
     * Không có quyền: hiện dạng che ("N. T. Lan", "091****678"); tỉnh/phường và số liệu vẫn hiện.
     */
    public const CUSTOMER_CONTACTS = 'customers.pii';
}
