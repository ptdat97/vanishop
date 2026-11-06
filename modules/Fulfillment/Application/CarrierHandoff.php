<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Domain\BusinessRuleViolation;
use Modules\Shared\Support\Phones;
use Modules\Shared\Support\StoreLocale;

/**
 * Trao đổi file với hãng vận chuyển (0.3.97) — shop đẩy đơn lên nhiều hãng bằng tay (SME, vision §2.1):
 *   - `rows()`: bảng các vận đơn chưa rời kho của đơn đã chọn — người nhận, địa chỉ, sản phẩm, khối lượng, giá trị hàng, COD;
 *     nhân viên dán vào mẫu nhập của hãng (cột "Hãng", "Mã vận đơn" để trống cho lần nhập lại).
 *   - `import()`: nhận lại mã vận đơn từ file (file hãng xuất hoặc chính file trên đã điền): tìm cột số đơn của shop theo dữ
 *     liệu, cột mã vận đơn và hãng theo tiêu đề; ghi mã cho vận đơn đang chờ mã của đơn đó.
 */
final class CarrierHandoff
{
    public const HEADERS = ['Mã đơn', 'Người nhận', 'Số điện thoại', 'Địa chỉ', 'Phường/Xã', 'Tỉnh/Thành phố', 'Sản phẩm', 'Số lượng', 'Khối lượng (gram)', 'Giá trị hàng', 'Tiền thu hộ (COD)', 'Hãng', 'Mã vận đơn'];

    private const ORDER_NUMBER = '/^[A-Z0-9]{1,16}\d{4}-\d{6,}$/';

    private const TRACKING_HEADER = '/(vận đơn|van don|tracking|waybill|mã vđ|ma vd|mã đơn hàng|ma don hang)/iu';

    private const CARRIER_HEADER = '/(hãng|hang vc|đơn vị vận chuyển|nhà vận chuyển|carrier|đvvc|dvvc)/iu';

    public function __construct(
        private readonly OrderReader $orders,
        private readonly CatalogReader $catalog,
        private readonly FulfillmentService $fulfillment,
    ) {}

    /**
     * @param  list<int>  $orderIds
     * @return list<list<string|int|null>> dòng tiêu đề + mỗi vận đơn chưa rời kho một dòng
     */
    public function rows(array $orderIds): array
    {
        $shipments = Shipment::query()->with('lines')->whereIn('order_id', $orderIds)
            ->whereIn('status', [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed, ShipmentStatus::Created])
            ->get()->sortBy(fn (Shipment $shipment): int => ((int) array_search($shipment->order_id, $orderIds, true)) * 1000 + $shipment->id);
        $weights = [];
        $variantIds = $shipments->flatMap(fn (Shipment $shipment) => $shipment->lines->pluck('variant_id'))->unique()->values()->all();
        foreach ($this->catalog->sellableVariants($variantIds, StoreLocale::default(), time()) as $id => $variant) {
            $weights[$id] = $variant->weightGram;
        }
        $defaultWeight = max(1, (int) config('vanishop.fulfillment.default_item_weight_gram', 300));

        $rows = [self::HEADERS];
        foreach ($shipments as $shipment) {
            $order = $this->orders->find($shipment->order_id);
            if ($order === null) {
                continue;
            }
            $lines = collect($this->orders->lines($order->id))->keyBy('id');
            $items = [];
            $quantity = 0;
            $weight = 0;
            $value = 0;
            foreach ($shipment->lines as $shipmentLine) {
                $line = $lines->get($shipmentLine->order_line_id);
                $variant = $line === null ? '' : trim(implode('/', array_filter([$line->colorName, $line->sizeCode])));
                $items[] = ($line?->productName ?? "#{$shipmentLine->variant_id}").($variant !== '' ? " ({$variant})" : '').' x'.$shipmentLine->quantity;
                $quantity += $shipmentLine->quantity;
                $weight += ($weights[$shipmentLine->variant_id] ?? $defaultWeight) * $shipmentLine->quantity;
                // Giá trị hàng: phần thành tiền (sau giảm) của số lượng trong vận đơn.
                $value += $line === null || $line->quantity === 0 ? 0 : intdiv($line->totalAmount * $shipmentLine->quantity, $line->quantity);
            }
            $address = $order->shippingAddress;
            $phone = (string) ($order->recipient['phone'] ?? '');

            $rows[] = [
                $order->number,
                (string) ($order->recipient['full_name'] ?? ''),
                Phones::parse($phone)?->national() ?? $phone,
                (string) ($address['street_line'] ?? ''),
                (string) ($address['ward_name'] ?? ''),
                (string) ($address['province_name'] ?? ''),
                implode('; ', $items),
                $quantity,
                $weight,
                $value,
                $shipment->cod_amount,
                $shipment->service_code,
                $shipment->tracking_number,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<list<string>>  $rows  dòng đầu là tiêu đề
     * @return array{booked: int, unchanged: int, errors: list<string>}
     */
    public function import(array $rows, ?string $defaultCarrier = null): array
    {
        $header = array_map(fn (string $cell): string => mb_strtolower(trim($cell)), $rows[0] ?? []);
        $data = array_slice($rows, 1, null, true);
        $orderColumn = $this->orderColumn($data);
        if ($orderColumn === null) {
            return ['booked' => 0, 'unchanged' => 0, 'errors' => ['Không tìm thấy cột số đơn của shop (dạng '.'ABC2611-000123'.') trong file.']];
        }
        $trackingColumn = $this->headerColumn($header, self::TRACKING_HEADER, [$orderColumn])
            ?? (count($header) === 2 || count($header) === 3 ? ($orderColumn === 0 ? 1 : 0) : null);
        if ($trackingColumn === null) {
            return ['booked' => 0, 'unchanged' => 0, 'errors' => ['Không tìm thấy cột mã vận đơn (tiêu đề có "vận đơn", "tracking"…).']];
        }
        $carrierColumn = $this->headerColumn($header, self::CARRIER_HEADER, [$orderColumn, $trackingColumn]);

        $booked = 0;
        $unchanged = 0;
        $errors = [];
        foreach ($data as $index => $row) {
            $number = strtoupper(trim((string) ($row[$orderColumn] ?? '')));
            $tracking = trim((string) ($row[$trackingColumn] ?? ''));
            $line = $index + 1;
            if ($number === '' && $tracking === '') {
                continue;
            }
            if ($tracking === '') {
                continue; // Chưa có mã (vd. file xuất chưa điền): bỏ qua, không báo lỗi.
            }
            if (preg_match('/^[A-Za-z0-9._-]{1,64}$/', $tracking) !== 1) {
                $errors[] = "Dòng {$line}: mã vận đơn \"{$tracking}\" không hợp lệ.";

                continue;
            }
            $order = $number === '' ? null : $this->orders->findByNumber($number);
            if ($order === null) {
                $errors[] = "Dòng {$line}: không có đơn {$number}.";

                continue;
            }
            $carrier = trim((string) ($carrierColumn === null ? '' : ($row[$carrierColumn] ?? ''))) ?: $defaultCarrier;
            $shipments = Shipment::query()->where('order_id', $order->id)->where('status', '!=', ShipmentStatus::Cancelled)->orderBy('id')->get();
            if ($shipments->contains(fn (Shipment $shipment): bool => $shipment->tracking_number === $tracking)) {
                $unchanged++;

                continue;
            }
            $waiting = $shipments->filter(fn (Shipment $shipment): bool => in_array($shipment->status, [ShipmentStatus::PendingBooking, ShipmentStatus::BookingFailed], true))->values();
            if ($waiting->isEmpty()) {
                $errors[] = "Dòng {$line}: đơn {$number} không có vận đơn đang chờ mã.";

                continue;
            }
            try {
                $this->fulfillment->book($waiting[0]->id, $tracking, $carrier !== null && $carrier !== '' ? mb_substr($carrier, 0, 64) : null);
                $booked++;
            } catch (BusinessRuleViolation $exception) {
                $errors[] = "Dòng {$line}: {$exception->getMessage()}";
            }
        }

        return ['booked' => $booked, 'unchanged' => $unchanged, 'errors' => $errors];
    }

    /**
     * Cột có nhiều giá trị đúng dạng số đơn của shop nhất (≥ nửa số dòng có dữ liệu).
     *
     * @param  array<int, list<string>>  $data
     */
    private function orderColumn(array $data): ?int
    {
        $hits = [];
        $filled = 0;
        foreach ($data as $row) {
            if (array_filter($row, fn (string $cell): bool => trim($cell) !== '') === []) {
                continue;
            }
            $filled++;
            foreach ($row as $column => $cell) {
                if (preg_match(self::ORDER_NUMBER, strtoupper(trim($cell))) === 1) {
                    $hits[$column] = ($hits[$column] ?? 0) + 1;
                }
            }
        }
        if ($hits === []) {
            return null;
        }
        arsort($hits);
        $column = (int) array_key_first($hits);

        return $filled <= $hits[$column] * 2 ? $column : null;
    }

    /**
     * @param  list<string>  $header
     * @param  list<int>  $exclude
     */
    private function headerColumn(array $header, string $pattern, array $exclude): ?int
    {
        foreach ($header as $column => $title) {
            if (! in_array($column, $exclude, true) && preg_match($pattern, $title) === 1) {
                return $column;
            }
        }

        return null;
    }
}
