/** Nhãn dùng chung cho Admin → Plugin. */
export const statusLabels: Record<string, string> = {
    discovered: 'Chưa cài',
    installed: 'Đã cài, chưa bật',
    enabled: 'Đang bật',
    draining: 'Đang ngừng',
    disabled: 'Đã tắt',
    failed: 'Lỗi',
};

export const statusClasses: Record<string, string> = {
    discovered: 'bg-slate-100 text-slate-600',
    installed: 'bg-sky-50 text-sky-700',
    enabled: 'bg-emerald-50 text-emerald-700',
    draining: 'bg-amber-50 text-amber-700',
    disabled: 'bg-slate-100 text-slate-600',
    failed: 'bg-red-50 text-red-700',
};

export const kindLabels: Record<string, string> = {
    payment_gateway: 'Thanh toán',
    shipping_carrier: 'Hãng vận chuyển',
    shipping_rate: 'Phí giao hàng',
    tax: 'Thuế',
    promotion: 'Khuyến mãi',
    notification_channel: 'Kênh thông báo',
    search: 'Tìm kiếm',
    integration: 'Tích hợp',
    image_format: 'Ảnh',
    video: 'Video',
    marketing: 'Marketing',
    analytics: 'Phân tích',
    customer_service: 'Chăm sóc khách',
    content: 'Nội dung',
    theme_extension: 'Giao diện',
    language: 'Ngôn ngữ',
    feature: 'Tính năng',
};
