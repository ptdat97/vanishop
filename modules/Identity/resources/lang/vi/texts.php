<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'identity_service_provider' => [
        'access_admin_area' => 'Truy cập trang quản trị',
        'manage_staff_roles' => 'Quản lý nhân viên và vai trò',
        'staff' => 'Nhân viên',
    ],
    'role_presets' => [
        'analyst' => [
            'description' => 'Chỉ xem: đơn, khách, sản phẩm, tồn, giá, khuyến mãi, báo cáo; xuất Excel. Thông tin liên hệ của khách bị che.',
            'name' => 'Phân tích dữ liệu',
        ],
        'content' => [
            'description' => 'Sản phẩm, danh mục, ảnh, trang, bài viết, giao diện cửa hàng; cài đặt SEO và Giao diện. Không sửa giá.',
            'name' => 'Nội dung',
        ],
        'marketing' => [
            'description' => 'Khuyến mãi, mã giảm giá, phân nhóm khách, mẫu tin nhắn, báo cáo; cài đặt plugin marketing. Không thấy SĐT/email khách.',
            'name' => 'Marketing',
        ],
        'order_ops' => [
            'description' => 'Xác nhận, đóng gói, giao hàng, đổi trả, xác nhận thanh toán, điều chỉnh tồn kho; xem liên hệ khách. Không hoàn tiền.',
            'name' => 'Vận hành đơn',
        ],
    ],
    'staff_password_controller' => [
        'reset_password' => 'Đặt lại mật khẩu',
        'set_first_password' => 'Đặt mật khẩu lần đầu',
    ],
];
