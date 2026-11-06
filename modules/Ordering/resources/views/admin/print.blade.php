{{--
    Phiếu giao / đóng gói (0.3.97) — in nhiều đơn một lần từ danh sách đơn hoặc trang đơn; mỗi đơn một trang A5. Trang HTML
    riêng (không qua giao diện Admin) để in trực tiếp từ trình duyệt; số lượng đã trừ phần huỷ; COD = số tiền thu hộ.
--}}
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Phiếu giao {{ count($orders) === 1 ? $orders[0]['number'] : count($orders).' đơn' }}</title>
    <style>
        @page { size: A5; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font: 13px/1.45 system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; color: #0f172a; background: #f1f5f9; }
        .toolbar { position: sticky; top: 0; display: flex; gap: 8px; align-items: center; justify-content: space-between; padding: 10px 16px; background: #fff; border-bottom: 1px solid #e2e8f0; }
        .toolbar button { padding: 6px 14px; border: 0; border-radius: 6px; background: #4f46e5; color: #fff; font: inherit; cursor: pointer; }
        .slip { width: 148mm; min-height: 200mm; margin: 16px auto; padding: 8mm; background: #fff; page-break-after: always; break-after: page; }
        .slip:last-child { page-break-after: auto; break-after: auto; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #0f172a; padding-bottom: 6px; margin-bottom: 8px; }
        .shop { font-weight: 700; font-size: 15px; }
        .number { font: 700 22px/1.1 ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: 0.5px; text-align: right; }
        .muted { color: #64748b; font-size: 11px; }
        h2 { margin: 10px 0 4px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.4px; color: #475569; }
        .to { font-size: 15px; }
        .to strong { font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th, td { padding: 4px 6px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
        th { font-size: 11px; color: #475569; }
        td.qty, th.qty { text-align: right; width: 40px; font-weight: 600; }
        .cod { margin-top: 10px; padding: 8px; border: 2px solid #0f172a; display: flex; justify-content: space-between; font-size: 16px; font-weight: 700; }
        .cod.paid { border-style: dashed; font-weight: 600; font-size: 13px; }
        .note { margin-top: 8px; padding: 6px 8px; background: #fef3c7; }
        @media print { body { background: #fff; } .toolbar { display: none; } .slip { margin: 0; width: auto; min-height: 0; padding: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>{{ count($orders) }} phiếu giao</span>
        <button type="button" onclick="window.print()">In</button>
    </div>
    @foreach ($orders as $order)
        <section class="slip" data-order="{{ $order['number'] }}">
            <div class="head">
                <div>
                    <div class="shop">{{ $shopName }}</div>
                    <div class="muted">Phiếu giao hàng · đặt {{ $order['placed_at'] }}</div>
                </div>
                <div>
                    <div class="number">{{ $order['number'] }}</div>
                    @if ($order['shipping_method'])<div class="muted" style="text-align:right">{{ $order['shipping_method'] }}</div>@endif
                </div>
            </div>

            <h2>Người nhận</h2>
            <div class="to">
                <strong>{{ $order['recipient'] }}</strong> · {{ $order['phone'] }}<br>
                {{ $order['address'] }}
            </div>

            <h2>Sản phẩm ({{ $order['item_count'] }})</h2>
            <table>
                <thead><tr><th>Sản phẩm</th><th class="qty">SL</th></tr></thead>
                <tbody>
                    @foreach ($order['lines'] as $line)
                        <tr>
                            <td>{{ $line['name'] }}<div class="muted">{{ $line['sku'] }} · {{ $line['variant'] }}</div></td>
                            <td class="qty">{{ $line['quantity'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if ($order['collect_on_delivery'] !== null)
                <div class="cod"><span>THU HỘ (COD)</span><span>{{ $order['collect_on_delivery'] }}</span></div>
            @else
                <div class="cod paid"><span>Đã thanh toán — không thu tiền</span><span>{{ $order['total'] }}</span></div>
            @endif
            @if ($order['note'])
                <div class="note">Ghi chú của khách: {{ $order['note'] }}</div>
            @endif
        </section>
    @endforeach
    <script>
        // Mở hộp in ngay khi trang tải xong (nút "In" vẫn có để in lại).
        window.addEventListener('load', () => window.print());
    </script>
</body>
</html>
