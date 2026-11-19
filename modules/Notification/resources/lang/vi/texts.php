<?php

// Chuỗi hiển thị từ server (ADR-049): nhãn định nghĩa, thông báo, lỗi — đọc bằng __('<namespace>::texts.<mục>.<khoá>').

return [
    'mail_settings' => [
        'encryption' => 'Mã hoá',
        'encryption_none' => 'Không mã hoá',
        'encryption_ssl' => 'SSL/TLS (thường cổng 465)',
        'encryption_tls' => 'STARTTLS (thường cổng 587)',
        'from_address' => 'Email người gửi',
        'from_address_help' => 'Địa chỉ khách thấy ở ô "Từ". Nên là email thuộc tên miền của cửa hàng và được phép gửi qua máy chủ SMTP ở trên. Để trống = theo cấu hình máy chủ.',
        'from_name' => 'Tên người gửi',
        'from_name_help' => 'Tên khách thấy ở ô "Từ", vd. tên cửa hàng. Để trống = theo cấu hình máy chủ.',
        'host' => 'Máy chủ SMTP',
        'host_help' => 'Vd. smtp.gmail.com, smtp.zoho.com, email-smtp.ap-southeast-1.amazonaws.com.',
        'password' => 'Mật khẩu SMTP',
        'password_help' => 'Gmail/Zoho: dùng mật khẩu ứng dụng, không dùng mật khẩu đăng nhập.',
        'port' => 'Cổng SMTP',
        'port_help' => '587 cho STARTTLS, 465 cho SSL/TLS.',
        'send_test' => 'Gửi email thử',
        'test_body' => "Đây là email thử từ trang quản trị cửa hàng.\n\nNếu bạn nhận được email này, cấu hình gửi email đã hoạt động.",
        'test_failed' => 'Không gửi được email thử: :error',
        'test_no_email' => 'Tài khoản của bạn chưa có email để nhận thư thử.',
        'test_sent' => 'Đã gửi email thử tới :email. Kiểm tra hộp thư (cả mục Spam).',
        'test_subject' => '[:store] Email thử',
        'transport' => 'Cách gửi email',
        'transport_help' => '"Theo máy chủ" dùng cấu hình MAIL_* trong .env. Chọn SMTP để dùng tài khoản email của cửa hàng bên dưới.',
        'transport_server' => 'Theo cấu hình máy chủ (.env)',
        'transport_smtp' => 'SMTP của cửa hàng',
        'username' => 'Tài khoản SMTP',
    ],
    'notification_service_provider' => [
        'edit_message_templates' => 'Sửa mẫu tin',
        'notification_channels' => 'Kênh gửi thông báo',
        'notifications' => 'Thông báo',
        'order_cancelled' => 'Đơn đã huỷ',
        'order_delivered' => 'Đã giao thành công',
        'order_out_delivery' => 'Đơn đang được giao',
        'order_placed' => 'Đặt đơn thành công',
        'view_message_templates_send_log' => 'Xem mẫu tin và nhật ký gửi',
    ],
];
