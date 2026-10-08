{{-- $order: OrderPresenter::present --}}
<ul class="divide-y divide-slate-200 border-y border-slate-200 text-sm">
    @foreach ($order['lines'] as $line)
        <li class="flex justify-between gap-4 py-3">
            <span>{{ $line['name'] }} ({{ $line['color_name'] }} / {{ $line['size_code'] }}) × {{ $line['quantity'] }}
                @include('theme::partials.line-options', ['options' => $line['options']])
            </span>
            <span>{{ $line['total']['formatted'] ?? '' }}</span>
        </li>
    @endforeach
</ul>
<dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
    @php($pricing = $order['pricing'] ?? null)
    @if ($pricing !== null && $pricing['markdown']['amount'] > 0)
        <div class="flex justify-between text-slate-500"><dt>Giá niêm yết</dt><dd>{{ $pricing['list_amount']['formatted'] }}</dd></div>
        <div class="flex justify-between text-slate-500"><dt>Giảm giá bán</dt><dd>-{{ $pricing['markdown']['formatted'] }}</dd></div>
    @endif
    <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ $order['subtotal']['formatted'] }}</dd></div>
    @if ($pricing !== null)
        @if ($pricing['promotion_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>Khuyến mãi</dt><dd>-{{ $pricing['promotion_discount']['formatted'] }}</dd></div>
        @endif
        @if ($pricing['voucher_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>Mã giảm giá @if ($pricing['vouchers'])<span class="font-mono text-xs">({{ implode(', ', $pricing['vouchers']) }})</span>@endif</dt><dd>-{{ $pricing['voucher_discount']['formatted'] }}</dd></div>
        @endif
        @if ($pricing['other_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>Giảm khác</dt><dd>-{{ $pricing['other_discount']['formatted'] }}</dd></div>
        @endif
    @elseif ($order['discount']['amount'] > 0)
        <div class="flex justify-between"><dt>Giảm giá</dt><dd>-{{ $order['discount']['formatted'] }}</dd></div>
    @endif
    <div class="flex justify-between"><dt>Phí giao</dt><dd>{{ $order['shipping_fee']['formatted'] }}</dd></div>
    <div class="flex justify-between text-base font-semibold"><dt>Tổng</dt><dd>{{ $order['total']['formatted'] }}</dd></div>
</dl>
