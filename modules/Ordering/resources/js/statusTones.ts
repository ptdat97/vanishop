type Tone = 'neutral' | 'info' | 'success' | 'warning' | 'danger';

/** Tông màu StatusBadge theo trạng thái đơn (chữ hiển thị do server dịch). */
export function orderStatusTone(status: string): Tone {
    return ({ pending: 'warning', confirmed: 'info', processing: 'info', completed: 'success', cancelled: 'danger' } as Record<string, Tone>)[status] ?? 'neutral';
}

/** Tông màu StatusBadge theo trạng thái thanh toán. */
export function paymentStatusTone(status: string): Tone {
    return ({ paid: 'success', cod_collected: 'success', authorized: 'info', cod_pending: 'warning', unpaid: 'warning', partially_refunded: 'neutral', refunded: 'neutral', failed: 'danger' } as Record<string, Tone>)[status] ?? 'neutral';
}
