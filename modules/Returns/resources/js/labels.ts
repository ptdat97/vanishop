/** Nhãn theo mã từ `returns::labels` (server dịch). */
export interface ReturnLabels {
    return_status: Record<string, string>;
    reason: Record<string, string>;
    condition: Record<string, string>;
}

/** Tông màu StatusBadge theo trạng thái yêu cầu đổi/trả. */
export function returnStatusTone(status: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    return ({ requested: 'warning', approved: 'info', in_transit: 'info', received: 'info', resolved: 'success', rejected: 'danger' } as const)[status as 'requested'] ?? 'neutral';
}
