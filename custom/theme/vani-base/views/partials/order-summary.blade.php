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
    <div class="flex justify-between"><dt>Tạm tính</dt><dd>{{ $order['subtotal']['formatted'] }}</dd></div>
    @if ($order['discount']['amount'] > 0)
        <div class="flex justify-between"><dt>Giảm giá</dt><dd>-{{ $order['discount']['formatted'] }}</dd></div>
    @endif
    <div class="flex justify-between"><dt>Phí giao</dt><dd>{{ $order['shipping_fee']['formatted'] }}</dd></div>
    <div class="flex justify-between text-base font-semibold"><dt>Tổng</dt><dd>{{ $order['total']['formatted'] }}</dd></div>
</dl>
