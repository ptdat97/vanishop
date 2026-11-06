<?php

return [
    'external_authority' => 'Tồn vật lý của location này do hệ thống ngoài [:authority] quản lý; không điều chỉnh tay ở VaniShop.',
    'stale' => 'Dữ liệu đã được người khác cập nhật. Vui lòng tải lại trang.',
    'saved' => 'Đã lưu.',
    'adjusted' => 'Đã cập nhật tồn.',
    'batch_reason' => 'Cập nhật tồn',
    'bulk_reason' => 'Cập nhật tồn hàng loạt',
    'bulk_done' => 'Tại :location: đã đổi :updated SKU, :unchanged SKU giữ nguyên.',
    'bulk_skipped' => ':count SKU bỏ qua (vd. trừ quá số tồn hiện có).',
    'bulk_negative' => 'Số lượng không được âm (chỉ "Cộng/trừ" nhận số âm).',
    'batch_saved' => 'Đã cập nhật :updated ô tồn.',
    'batch_conflicts' => ':count ô không lưu vì tồn đã thay đổi sau khi mở trang (vd. vừa xuất kho) — đã tải lại số mới.',
    'default_created' => 'Đã tạo kho :name. Giờ có thể nhập tồn.',
    'default_exists' => 'Cửa hàng đã có kho.',
    'import_reason' => 'Nhập CSV :file',
    'import_applied' => 'Đã nhập tồn: :updated dòng.',
    'import_conflicts' => ':count dòng bỏ qua vì tồn đã thay đổi sau khi xem trước.',
    'import_nothing' => 'File không có dòng nào cần thay đổi.',
    'import_expired' => 'Phiên nhập đã hết hạn (30 phút). Vui lòng tải file lên lại.',
    'import_unreadable' => 'Không đọc được file.',
    'import_header' => 'Dòng đầu của file phải có cột sku và on_hand (location tuỳ chọn).',
    'import_too_many' => 'Tối đa :max dòng mỗi lần nhập.',

    'location_missing' => 'Kho không tồn tại.',
    'transfer_external_authority' => 'Chỉ chuyển kho giữa các location do VaniShop quản lý tồn vật lý.',

    'reconcile_source_required' => '--source là bắt buộc và phải khác "vanishop".',
    'reconcile_input_required' => 'Cần --file hoặc --json cho snapshot.',
    'reconcile_invalid_json' => 'JSON snapshot không hợp lệ.',
];
