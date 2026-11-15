type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

export type DeliveryHistory = { orders: number; delivered: number; returned_to_sender: number; cancelled: number };

/** Nhãn rủi ro khách theo lịch sử nhận hàng (SĐT) — đánh giá trước khi xác nhận đơn COD (chốt ①, ADR-046). */
export function customerRiskFlag(history: DeliveryHistory | null): { label: string; tone: 'danger' | 'warning' | 'success' } | null {
    if (history === null) return null;
    if (history.returned_to_sender > 0) return { label: `Từng hoàn hàng ${history.returned_to_sender} lần`, tone: 'danger' };
    if (history.orders === 0) return { label: 'Khách mới', tone: 'warning' };
    if (history.delivered > 0) return { label: 'Khách quen', tone: 'success' };
    return null;
}

/** Tông màu StatusBadge theo trạng thái đơn (chữ hiển thị do server dịch). */
export function orderStatusTone(status: string): Tone {
    return ({ pending: 'warning', confirmed: 'info', processing: 'info', completed: 'success', cancelled: 'danger' } as Record<string, Tone>)[status] ?? 'neutral';
}

/** Tông màu StatusBadge theo trạng thái thanh toán. */
export function paymentStatusTone(status: string): Tone {
    return ({ paid: 'success', cod_collected: 'success', authorized: 'info', cod_pending: 'warning', unpaid: 'warning', partially_refunded: 'neutral', refunded: 'neutral', failed: 'danger', voided: 'neutral' } as Record<string, Tone>)[status] ?? 'neutral';
}

/** Tông màu giai đoạn vận hành (OrderStage, 0.3.96). */
export function stageTone(stage: string): Tone {
    return (
        {
            to_confirm: 'warning',
            awaiting_payment: 'warning',
            to_ship: 'info',
            shipping: 'info',
            issue: 'danger',
            returns: 'danger',
            delivered: 'success',
            completed: 'success',
            cancelled: 'neutral',
        } as Record<string, Tone>
    )[stage] ?? 'neutral';
}
