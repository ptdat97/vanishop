{{-- Mẫu phiếu giao A6 cho máy in nhiệt (EP v3 X3 — tham chiếu PackingSlipTemplate). Dữ liệu: xem Ordering\Contracts\PackingSlipTemplate. --}}
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Phiếu giao A6</title>
    <style>
        @page { size: A6; margin: 4mm; }
        body { font: 11px/1.35 system-ui, sans-serif; margin: 0; }
        .toolbar { padding: 8px; background: #f1f5f9; display: flex; gap: 12px; align-items: center; }
        .slip { width: 97mm; padding: 3mm; page-break-after: always; border-bottom: 1px dashed #94a3b8; }
        .number { font-size: 16px; font-weight: 700; }
        .cod { font-size: 14px; font-weight: 700; margin-top: 2mm; }
        .barcode svg { width: 100%; height: 12mm; }
        @media print { .toolbar { display: none; } .slip { border: 0; } }
    </style>
</head>
<body>
    <div class="toolbar">
        <span>{{ count($orders) }} phiếu (A6)</span>
        <span>Mẫu:
            @foreach ($templates as $template)
                @if ($template['code'] === $current)<strong>{{ $template['label'] }}</strong>@else<a href="{{ $template['url'] }}">{{ $template['label'] }}</a>@endif{{ $loop->last ? '' : ' · ' }}
            @endforeach
        </span>
        <button type="button" onclick="window.print()">In</button>
    </div>
    @foreach ($orders as $order)
        <section class="slip" data-slip-a6="{{ $order['number'] }}">
            <div class="number">{{ $order['number'] }}</div>
            <div class="barcode">{!! $order['barcode'] !!}</div>
            <div><strong>{{ $order['recipient'] }}</strong> · {{ $order['phone'] }}</div>
            <div>{{ $order['address'] }}</div>
            <div>{{ $order['item_count'] }} sản phẩm · {{ $order['shipping_method'] }}</div>
            @if ($order['collect_on_delivery'])<div class="cod">Thu hộ: {{ $order['collect_on_delivery'] }}</div>@endif
            <div>Cho khách xem hàng · Cảm ơn bạn đã mua hàng tại {{ $shopName }}!</div>
        </section>
    @endforeach
</body>
</html>
