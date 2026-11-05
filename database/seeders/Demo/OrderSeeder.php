<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\Carts;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Checkout\Contracts\AddressDirectory;
use Modules\Checkout\Contracts\Checkout;
use Modules\Checkout\Contracts\Data\CheckoutRequest;
use Modules\Checkout\Contracts\ShippingAddresses;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Ordering\Application\OrderCommands;
use Modules\Ordering\Contracts\Data\OrderStatus;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderTransitions;
use Modules\Payment\Contracts\Payments;
use Modules\Shared\Domain\Money\Money;
use Throwable;

/**
 * Khách + đơn hàng đi đúng luồng thật (giỏ → checkout → giữ hàng → vận đơn → giao/huỷ) ở nhiều trạng thái: đang xử lý
 * (COD tự xác nhận khi đặt), đang giao, đã giao (COD đã thu), hoàn tất, đã huỷ. Ngày đặt rải trong 20 ngày để báo cáo, "Bán chạy", "Đã bán N" có số.
 * Chỉ chạy khi chưa có đơn demo (ghi chú "[demo]").
 */
final class OrderSeeder extends Seeder
{
    private const NOTE = '[demo] Đơn mẫu';

    private const CUSTOMERS = [
        ['Nguyễn Thị Lan', '0912345678', 'lan.nguyen@example.com', '12 Lê Lợi'],
        ['Trần Minh Anh', '0987654321', 'minhanh@example.com', '45 Nguyễn Huệ'],
        ['Lê Hoàng Mai', '0903111222', null, '8 Pasteur'],
        ['Phạm Thu Hà', '0938555666', 'thuha@example.com', '120 Võ Văn Tần'],
        ['Đỗ Ngọc Linh', '0977123123', null, '3 Hai Bà Trưng'],
        ['Vũ Khánh Vy', '0966888999', 'khanhvy@example.com', '27 Điện Biên Phủ'],
    ];

    /** [khách, số dòng, phương thức, trạng thái, số ngày trước]. */
    private const ORDERS = [
        [0, 2, 'cod', 'delivered', 18], [1, 1, 'cod', 'delivered', 15], [2, 3, 'cod', 'delivered', 12], [3, 1, 'cod', 'delivered', 9],
        [4, 2, 'cod', 'delivered', 6], [5, 1, 'cod', 'shipping', 4], [0, 1, 'cod', 'shipping', 3], [1, 2, 'cod', 'confirmed', 2],
        [2, 1, 'manual_bank_transfer', 'pending', 1], [3, 2, 'cod', 'pending', 0], [4, 1, 'cod', 'cancelled', 5], [5, 2, 'cod', 'delivered', 2],
    ];

    public function run(Carts $carts, Checkout $checkout, ShippingAddresses $shippingAddresses, AvailabilityReader $availability, OrderReader $orders, OrderCommands $commands, FulfillmentService $fulfillment, Payments $payments, OrderTransitions $transitions): void
    {
        if (DB::table('orders')->where('note', self::NOTE)->exists()) {
            return;
        }
        $addresses = $shippingAddresses->directory();
        $address = $addresses === null ? null : $this->address($addresses);
        if ($address === null) {
            $this->command?->warn('Bỏ qua đơn demo: chưa có danh mục tỉnh/phường (bật plugin vani.provinces-vn).');

            return;
        }

        $variantIds = Variant::query()->whereHas('style', fn ($query) => $query->where('style_code', 'like', 'VS-%')->where('status', 'active'))->orderBy('id')->pluck('id')->all();

        foreach (self::ORDERS as $index => [$customer, $lines, $payment, $stage, $daysAgo]) {
            [$name, $phone, $email, $street] = self::CUSTOMERS[$customer];
            // Đọc lại tồn trước mỗi đơn: chỉ chọn SKU còn bán được (đơn trước đã giữ / trừ hàng).
            $ats = $availability->forVariants($variantIds);
            $sellable = array_values(array_filter($variantIds, fn (int $id): bool => ($ats[$id] ?? 0) > 0));
            if ($sellable === []) {
                $this->command?->warn('Dừng tạo đơn demo: hết SKU còn hàng ở kho giao online.');

                break;
            }
            try {
                $cart = $carts->create('VND');
                foreach (array_unique(array_map(fn (int $line): int => $sellable[($index * 3 + $line * 7) % count($sellable)], range(0, $lines - 1))) as $variantId) {
                    $carts->addLine($cart->key, $variantId, 1);
                }
                // Chuyển khoản cần tài khoản ngân hàng trong cấu hình plugin; chưa có thì dùng COD.
                $methods = array_column($payments->availableMethods(Money::vnd(500_000)), 'code');
                $payment = in_array($payment, $methods, true) ? $payment : 'cod';
                $result = $checkout->placeOrder(new CheckoutRequest(
                    cart: $cart->key,
                    contact: ['full_name' => $name, 'phone' => $phone, 'email' => $email],
                    shippingAddress: [...$address, 'street_line' => $street],
                    shippingMethod: 'standard', paymentMethod: $payment, voucherCodes: [], note: self::NOTE, expectedTotal: null,
                ), "demo-order-{$index}");
                $number = (string) ($result->body['number'] ?? $result->body['data']['number'] ?? '');
                $order = $orders->findByNumber($number);
                if ($order === null) {
                    continue;
                }
                $this->advance($order->id, $stage, $commands, $fulfillment, $transitions);
                // Đơn đã giao lâu (quá hạn đổi trả) → hoàn tất.
                if ($stage === 'delivered' && $daysAgo >= 9 && $transitions->can($order->id, OrderStatus::Completed)) {
                    $transitions->transition($order->id, OrderStatus::Completed, 'Hoàn tất (demo)', 'staff');
                }
                $at = now()->subDays($daysAgo)->setTime(9 + $index % 10, ($index * 17) % 60);
                DB::table('orders')->where('id', $order->id)->update(['placed_at' => $at, 'created_at' => $at]);
            } catch (Throwable $exception) {
                $this->command?->warn("Đơn demo #{$index} bỏ qua: {$exception->getMessage()}");
            }
        }
    }

    private function advance(int $orderId, string $stage, OrderCommands $commands, FulfillmentService $fulfillment, OrderTransitions $transitions): void
    {
        if ($stage === 'pending') {
            return;
        }
        if ($stage === 'cancelled') {
            $commands->cancel($orderId, 'Khách đổi ý (demo)', 'staff');

            return;
        }
        // Đơn có thể đã được xác nhận tự động (vd. khách quen) — chỉ xác nhận khi còn chờ.
        if ($transitions->can($orderId, OrderStatus::Confirmed)) {
            $commands->confirm($orderId, 'Xác nhận (demo)');
        }
        if ($stage === 'confirmed') {
            return;
        }
        $shipment = Shipment::query()->where('order_id', $orderId)->latest('id')->first();
        if ($shipment === null) {
            return;
        }
        if ($shipment->tracking_number === null) {
            $fulfillment->book($shipment->id, 'DEMO'.$shipment->id);
        }
        $statuses = $stage === 'delivered'
            ? [ShipmentStatus::PickedUp, ShipmentStatus::InTransit, ShipmentStatus::Delivered]
            : [ShipmentStatus::PickedUp, ShipmentStatus::InTransit];
        foreach ($statuses as $status) {
            $fulfillment->updateStatus($shipment->id, $status, "demo:{$shipment->id}:{$status->value}", 'staff');
        }
    }

    /**
     * Phường đầu tiên của TP. Hồ Chí Minh (hoặc tỉnh đầu tiên) trong danh mục địa chỉ.
     *
     * @return array{province_code: string, province_name: string, ward_code: string, ward_name: string}|null
     */
    private function address(AddressDirectory $addresses): ?array
    {
        $provinces = $addresses->provinces();
        $province = collect($provinces)->first(fn (array $item): bool => str_contains($item['name'], 'Hồ Chí Minh')) ?? ($provinces[0] ?? null);
        $ward = $province === null ? null : ($addresses->wards($province['code'])[0] ?? null);

        return $province === null || $ward === null ? null
            : ['province_code' => $province['code'], 'province_name' => $province['name'], 'ward_code' => $ward['code'], 'ward_name' => $ward['name']];
    }
}
