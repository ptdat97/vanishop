<?php

/*
| Thông điệp lỗi chung của /api/* (ApiErrorRenderer) — không lộ tên class, câu mặc định tiếng Anh của framework.
*/
return [
    'validation' => 'Dữ liệu không hợp lệ.',
    'validation_row' => 'dòng :number',
    'server' => 'Đã có lỗi xảy ra. Vui lòng thử lại sau.',
    'http' => [
        400 => 'Yêu cầu không hợp lệ.',
        401 => 'Cần đăng nhập.',
        403 => 'Không có quyền thực hiện thao tác này.',
        404 => 'Không tìm thấy.',
        405 => 'Phương thức không được hỗ trợ.',
        409 => 'Xung đột dữ liệu, vui lòng thử lại.',
        413 => 'Dữ liệu gửi lên quá lớn.',
        415 => 'Định dạng dữ liệu không được hỗ trợ.',
        419 => 'Phiên làm việc đã hết hạn. Tải lại trang rồi thử lại.',
        429 => 'Quá nhiều yêu cầu, vui lòng thử lại sau.',
        503 => 'Hệ thống đang bảo trì, vui lòng quay lại sau.',
    ],
];
