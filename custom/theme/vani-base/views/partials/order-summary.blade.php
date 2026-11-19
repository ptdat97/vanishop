{{-- $order: OrderPresenter::present --}}
<ul class="divide-y divide-slate-200 border-y border-slate-200 text-sm">
    @foreach ($order['lines'] as $line)
        <li class="flex justify-between gap-4 py-3">
            <span>{{ $line['name'] }}@if (($line['variant_label'] ?? '') !== '') ({{ $line['variant_label'] }})@endif × {{ $line['quantity'] }}
                @include('theme::partials.line-options', ['options' => $line['options']])
            </span>
            <span>{{ $line['total']['formatted'] ?? '' }}</span>
        </li>
    @endforeach
</ul>
<dl class="ml-auto mt-4 max-w-xs space-y-1 text-sm">
    @php($pricing = $order['pricing'] ?? null)
    @if ($pricing !== null && $pricing['markdown']['amount'] > 0)
        <div class="flex justify-between text-slate-500"><dt>{{ __('theme::checkout.list_price') }}</dt><dd>{{ $pricing['list_amount']['formatted'] }}</dd></div>
        <div class="flex justify-between text-slate-500"><dt>{{ __('theme::checkout.sale_discount') }}</dt><dd>-{{ $pricing['markdown']['formatted'] }}</dd></div>
    @endif
    <div class="flex justify-between"><dt>{{ __('theme::checkout.subtotal') }}</dt><dd>{{ $order['subtotal']['formatted'] }}</dd></div>
    @if ($pricing !== null)
        @if ($pricing['promotion_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>{{ __('theme::checkout.promotion') }}</dt><dd>-{{ $pricing['promotion_discount']['formatted'] }}</dd></div>
        @endif
        @if ($pricing['voucher_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>{{ __('theme::common.discount_code') }} @if ($pricing['vouchers'])<span class="font-mono text-xs">({{ implode(', ', $pricing['vouchers']) }})</span>@endif</dt><dd>-{{ $pricing['voucher_discount']['formatted'] }}</dd></div>
        @endif
        @if ($pricing['other_discount']['amount'] > 0)
            <div class="flex justify-between"><dt>{{ __('theme::checkout.other_discount') }}</dt><dd>-{{ $pricing['other_discount']['formatted'] }}</dd></div>
        @endif
    @elseif ($order['discount']['amount'] > 0)
        <div class="flex justify-between"><dt>{{ __('theme::common.discount') }}</dt><dd>-{{ $order['discount']['formatted'] }}</dd></div>
    @endif
    <div class="flex justify-between"><dt>{{ __('theme::checkout.shipping') }}</dt><dd>{{ $order['shipping_fee']['formatted'] }}</dd></div>
    <div class="flex justify-between text-base font-semibold"><dt>{{ __('theme::checkout.total') }}</dt><dd>{{ $order['total']['formatted'] }}</dd></div>
</dl>
