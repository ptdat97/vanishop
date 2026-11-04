/** Nhãn theo mã từ `promotion::labels` (server dịch). */
export interface PromotionLabels {
    campaign_state: Record<string, string>;
    promotion_status: Record<string, string>;
    stacking: Record<string, string>;
}

/** Tông màu StatusBadge theo trạng thái campaign. */
export function campaignStateTone(state: string): 'neutral' | 'info' | 'success' | 'warning' | 'danger' {
    return ({ scheduled: 'warning', running: 'success', stopped: 'danger' } as const)[state as 'scheduled'] ?? 'neutral';
}
