type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

/** Tông màu nhãn trạng thái bảng giá: đang chạy / sắp chạy / đã hết hạn / tắt. */
export function stateTone(state: string): Tone {
    return ({ running: 'success', scheduled: 'info', expired: 'neutral', disabled: 'neutral' } as Record<string, Tone>)[state] ?? 'neutral';
}

/** Tông màu cảnh báo giá (không chặn lưu): 0đ, không thấp hơn giá niêm yết, giảm vượt mức trần, chưa có giá niêm yết. */
export function warningTone(warning: string): Tone {
    return ({ zero: 'warning', not_lower: 'danger', over_cap: 'warning', no_base: 'neutral' } as Record<string, Tone>)[warning] ?? 'neutral';
}
