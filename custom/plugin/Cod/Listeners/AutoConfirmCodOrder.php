<?php

declare(strict_types=1);

namespace Plugin\Cod\Listeners;

use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Ordering\Events\OrderPlaced;
use Modules\Tenancy\Contracts\Settings;
use Plugin\Cod\CodServiceProvider;

/**
 * Điểm mở rộng của chốt ① (ADR-046): tự xác nhận đơn COD sau khi đặt, theo Admin → Cấu hình → vani.cod →
 * "Tự xác nhận đơn COD" (1.1.0):
 *   `off`     — mặc định (1.2.0): nhân viên gọi/nhắn xác nhận mọi đơn;
 *   `trusted` — chỉ khi SĐT đã có ≥ N đơn giao thành công và chưa từng bị hoàn về (chống bom hàng); còn lại nhân viên gọi;
 *   `all`     — xác nhận ngay mọi đơn COD.
 * Chưa chọn: theo công tắc cũ `auto_confirm` (1.0) rồi VANI_COD_AUTO_CONFIRM (mặc định tắt → `off`).
 * Tự xác nhận đi qua cùng `OrderTransitions` như nhân viên, nguồn `system`, lý do `cod_auto_confirm[_trusted]`.
 */
final class AutoConfirmCodOrder
{
    public const MODES = ['all', 'trusted', 'off'];

    public function __construct(
        private readonly OrderReader $orders,
        private readonly OrderTransitions $transitions,
        private readonly Settings $settings,
    ) {}

    public function handle(OrderPlaced $event): void
    {
        $mode = $this->mode();
        if ($mode === 'off') {
            return;
        }

        $order = $this->orders->find($event->orderId);
        if ($order === null || $order->paymentStatus !== 'cod_pending' || $order->status !== OrderStatus::Pending) {
            return;
        }
        if ($mode === 'trusted' && ! $this->trusted($order->recipient['phone'], $order->id)) {
            return;
        }

        $this->transitions->transition($order->id, OrderStatus::Confirmed, $mode === 'all' ? 'cod_auto_confirm' : 'cod_auto_confirm_trusted', 'system');
    }

    public function mode(): string
    {
        $explicit = $this->settings->explicit(CodServiceProvider::ID);
        $mode = (string) ($explicit['auto_confirm_mode'] ?? '');
        if (in_array($mode, self::MODES, true)) {
            return $mode;
        }
        $legacy = $explicit['auto_confirm'] ?? config('vani.cod.auto_confirm', false);

        return (bool) $legacy ? 'all' : 'off';
    }

    private function trusted(string $phone, int $orderId): bool
    {
        $history = $this->orders->deliveryHistory($phone, $orderId);
        $minimum = max(1, (int) $this->settings->get(CodServiceProvider::ID, 'trusted_min_delivered', 1));

        return $history['returned_to_sender'] === 0 && $history['delivered'] >= $minimum;
    }
}
