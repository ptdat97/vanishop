<?php

declare(strict_types=1);

namespace Plugin\Preorder;

use DateTimeImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Extension\PluginServiceProvider;
use Modules\Fulfillment\Contracts\Data\ShipmentData;
use Modules\Fulfillment\Contracts\Data\ShipmentView;
use Modules\Fulfillment\Contracts\ShipmentReader;
use Modules\Inventory\Contracts\BackorderPolicy;
use Modules\Inventory\Contracts\Backorders;
use Modules\Inventory\Contracts\IncomingStock;
use Modules\Inventory\Events\StockAdjusted;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Support\SpreadsheetRows;
use Modules\Storefront\Contracts\Data\SlotView;
use Modules\Storefront\Contracts\StorefrontEnricher;
use Plugin\Preorder\Infrastructure\IncomingBackorderPolicy;
use Plugin\Preorder\Infrastructure\PreorderProductEnricher;

/**
 * Đặt trước hàng đang về (ADR-047, EP v3 X7 — tham chiếu cho `BackorderPolicy` và `IncomingStock`): nhân viên nhập hàng
 * đang về từ file ở trang Tồn kho → SKU hết hàng mở bán đặt trước tối đa theo số đang về; nhập kho thì trừ dần số đang về.
 * Đơn còn chờ hàng không được đẩy lên hãng (chốt có người, ADR-046).
 */
final class PreorderServiceProvider extends PluginServiceProvider
{
    public const ID = 'vani.preorder';

    protected function pluginId(): string
    {
        return self::ID;
    }

    public function boot(): void
    {
        $this->loadJsonTranslationsFrom($this->pluginPath('Resources/lang'));
        $this->settings([
            ['key' => 'share_percent', 'label' => 'Mở bán đặt trước bao nhiêu % hàng đang về (0–100)', 'type' => 'int', 'default' => 100, 'rules' => ['min:0', 'max:100'],
                'help' => 'Vd. 50: đang về 40 cái thì mở 20 suất đặt trước. 0 = tạm dừng đặt trước, vẫn hiện "Đang về" ở Tồn kho.'],
            ['key' => 'block_push', 'label' => 'Không cho đẩy lên hãng đơn còn chờ hàng', 'type' => 'bool', 'default' => true],
        ]);
        $this->contribute(BackorderPolicy::TAG, IncomingBackorderPolicy::class);

        // Nhập kho (+) → trừ dần số đang về để hạn mức không cộng trùng với hàng đã về.
        $this->onEvent(StockAdjusted::class, function (StockAdjusted $event): void {
            if ($event->onHandDelta <= 0) {
                return;
            }
            $incoming = $this->app->make(IncomingStock::class);
            $current = $incoming->forVariants([$event->variantId])[$event->variantId] ?? null;
            if ($current !== null) {
                $incoming->set($event->variantId, $current->quantity - $event->onHandDelta, $current->expectedOn, $current->source);
            }
        });

        $this->extendStockScreen();
        $this->guardFulfillment();

        $this->storefrontViews($this->pluginPath('Resources/views'), 'vani-preorder');
        $this->contribute(StorefrontEnricher::TAG, PreorderProductEnricher::class);
        $this->onSlot('vani.storefront.pdp.add_to_cart_fields', function (array $product): ?SlotView {
            $dates = (array) ($product['extensions'][self::ID]['variants'] ?? []);

            return $dates === [] ? null : new SlotView('vani-preorder::pdp-preorder', ['dates' => array_map(fn (?string $date): ?string => self::shortDate($date), $dates), 'scriptSrc' => vani_asset(self::ID, 'preorder.js')]);
        });
    }

    /**
     * Trang Tồn kho (EP v3 X1): cột "Đang về" + công cụ nhập hàng đang về từ file.
     */
    private function extendStockScreen(): void
    {
        $this->adminColumn('stock', 'incoming', 'Đang về', resolve: function (array $variantIds): array {
            $status = $this->app->make(Backorders::class)->status($variantIds);
            $rows = [];
            foreach ($this->app->make(IncomingStock::class)->forVariants($variantIds) as $variantId => $incoming) {
                $rows[$variantId] = $incoming->quantity.($incoming->expectedOn === null ? '' : ' · về '.self::shortDate($incoming->expectedOn))
                    .(($status[$variantId]['waiting'] ?? 0) > 0 ? ' · chờ hàng '.$status[$variantId]['waiting'] : '');
            }

            return $rows;
        });
        $this->adminTool('stock', 'import_incoming', 'Nhập hàng đang về', 'inventory.adjust',
            handle: function (UploadedFile $file, array $fields): string {
                $rows = array_values(array_filter(SpreadsheetRows::fromUpload($file), fn (array $row): bool => trim((string) ($row[0] ?? '')) !== ''));
                $variants = $this->app->make(VariantDirectory::class)->findBySkus(array_map(fn (array $row): string => trim((string) $row[0]), $rows));
                $incoming = $this->app->make(IncomingStock::class);
                $saved = 0;
                $unknown = [];
                foreach ($rows as $index => $row) {
                    $sku = trim((string) $row[0]);
                    $variant = $variants[$sku] ?? null;
                    if ($variant === null) {
                        // Dòng tiêu đề (vd. "SKU") không báo lỗi.
                        if ($index > 0) {
                            $unknown[] = $sku;
                        }

                        continue;
                    }
                    $date = self::parseDate(trim((string) ($row[2] ?? '')));
                    if ($date === false) {
                        throw ValidationException::withMessages(['file' => "Ngày về của {$sku} không hợp lệ — dùng dd/mm/yyyy."]);
                    }
                    $incoming->set($variant->id, (int) ($row[1] ?? 0), $date, 'staff');
                    $saved++;
                }
                if ($saved === 0) {
                    throw ValidationException::withMessages(['file' => 'File không có SKU nào khớp ở cột đầu.']);
                }

                return "Đã cập nhật hàng đang về cho {$saved} SKU.".($unknown === [] ? '' : ' Không tìm thấy: '.implode(', ', array_slice($unknown, 0, 10)).(count($unknown) > 10 ? '…' : '').'.');
            },
            help: 'File .csv/.xlsx: cột 1 SKU, cột 2 số lượng đang về (0 = xoá), cột 3 ngày dự kiến dd/mm/yyyy (tuỳ chọn).',
        );
    }

    /**
     * Chốt đẩy hãng (hook vani.fulfillment.before_push) và thẻ "Đặt trước" trên trang đơn.
     */
    private function guardFulfillment(): void
    {
        $waiting = function (?string $reservationKey): array {
            $quantities = $reservationKey === null || $reservationKey === '' ? [] : $this->app->make(Backorders::class)->waitingFor($reservationKey);
            if ($quantities === []) {
                return [];
            }
            $variants = $this->app->make(VariantDirectory::class)->find(array_keys($quantities));
            $status = $this->app->make(Backorders::class)->status(array_keys($quantities));
            $rows = [];
            foreach ($quantities as $variantId => $quantity) {
                $date = $status[$variantId]['expected_on'] ?? null;
                $rows[] = ['label' => ($variants[$variantId]->sku ?? "#{$variantId}")." × {$quantity}", 'value' => $date === null ? 'chờ hàng về' : 'dự kiến '.self::shortDate($date)];
            }

            return $rows;
        };

        $this->onValidate('vani.fulfillment.before_push', function (ShipmentData $shipment): array {
            if (! (bool) vani_setting(self::ID, 'block_push', true)) {
                return [];
            }
            $order = $this->app->make(OrderReader::class)->findByNumber($shipment->orderNumber);
            $short = $order === null ? [] : $this->shortInShipment($order->id, (string) $order->reservationKey, $shipment->publicId);

            return $short === [] ? [] : ['Vận đơn còn món chờ hàng: '.implode(', ', $short).' — nhập kho khi hàng về rồi đẩy lên hãng, hoặc "Giao trước phần có hàng" trên trang đơn'];
        });
        // Nhắc CSKH (L37): SKU đặt trước còn đơn chờ mà đã quá ngày dự kiến có hàng → cập nhật ngày hàng về hoặc báo khách.
        $this->onSlot('vani.admin.dashboard.tasks', function (): array {
            $due = $this->app->make(IncomingStock::class)->dueBy(now()->subDay()->format('Y-m-d'));
            $overdue = $due === [] ? 0 : count(array_filter($this->app->make(Backorders::class)->status(array_keys($due)), fn (array $row): bool => $row['waiting'] > 0));

            return [['key' => 'preorder.overdue', 'label' => 'Đặt trước quá ngày dự kiến (SKU còn đơn chờ)', 'count' => $overdue, 'url' => route('admin.inventory.stock.index'), 'tone' => 'warning', 'order' => 35]];
        });
        $this->onSlot('vani.admin.order.sidebar', function (OrderDetail $order) use ($waiting): ?array {
            $rows = $waiting($this->app->make(OrderReader::class)->find($order->id)?->reservationKey);

            return $rows === [] ? null : ['title' => 'Đặt trước — chờ hàng', 'rows' => $rows];
        });
    }

    /**
     * Món của vận đơn `$shipmentPublicId` chưa có hàng: số có hàng của mỗi SKU (đặt − chờ) chia cho các vận đơn của đơn theo
     * thứ tự tạo (vận đơn trước nhận trước); vận đơn này thiếu thì chặn đẩy. Đơn đã "Giao trước phần có hàng" → kiện có
     * hàng đẩy được, kiện giao sau bị chặn tới khi hàng về.
     *
     * @return list<string> "SKU × số thiếu"
     */
    private function shortInShipment(int $orderId, string $reservationKey, string $shipmentPublicId): array
    {
        $waiting = $reservationKey === '' ? [] : $this->app->make(Backorders::class)->waitingFor($reservationKey);
        if ($waiting === []) {
            return [];
        }
        $shipments = array_values(array_filter($this->app->make(ShipmentReader::class)->forOrder($orderId), fn (ShipmentView $view): bool => $view->status !== 'cancelled'));
        usort($shipments, fn (ShipmentView $a, ShipmentView $b): int => $a->id <=> $b->id);
        $ordered = [];
        foreach ($shipments as $view) {
            foreach ($view->lines as $line) {
                $ordered[$line['variant_id']] = ($ordered[$line['variant_id']] ?? 0) + $line['quantity'];
            }
        }
        $available = [];
        foreach ($ordered as $variantId => $quantity) {
            $available[$variantId] = max(0, $quantity - ($waiting[$variantId] ?? 0));
        }
        $variants = $this->app->make(VariantDirectory::class)->find(array_keys($waiting));
        foreach ($shipments as $view) {
            $short = [];
            foreach ($view->lines as $line) {
                $given = min($line['quantity'], $available[$line['variant_id']] ?? 0);
                $available[$line['variant_id']] = ($available[$line['variant_id']] ?? 0) - $given;
                if ($line['quantity'] > $given) {
                    $short[] = ($variants[$line['variant_id']]->sku ?? "#{$line['variant_id']}").' × '.($line['quantity'] - $given);
                }
            }
            if ($view->publicId === $shipmentPublicId) {
                return $short;
            }
        }

        return [];
    }

    private static function shortDate(?string $date): ?string
    {
        if ($date === null) {
            return null;
        }
        $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed === false ? $date : $parsed->format('d/m');
    }

    /**
     * dd/mm/yyyy hoặc yyyy-mm-dd → Y-m-d; trống → null; sai → false.
     */
    private static function parseDate(string $value): string|false|null
    {
        if ($value === '') {
            return null;
        }
        foreach (['!d/m/Y', '!Y-m-d'] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false && $date->format(substr($format, 1)) === $value) {
                return $date->format('Y-m-d');
            }
        }

        return false;
    }
}
