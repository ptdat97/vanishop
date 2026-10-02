{{-- $price: khoảng giá của ProductViews (min/max/compare_at/discount_percent) hoặc null. --}}
@if ($price === null)
    <span class="text-sm text-slate-500">Chưa có giá</span>
@else
    <span class="font-semibold text-primary" data-price>
        {{ $price['min']['formatted'] }}@if ($price['max']['amount'] !== $price['min']['amount']) – {{ $price['max']['formatted'] }}@endif
    </span>
    @if ($price['compare_at'])
        <s class="ml-1 text-sm text-slate-400">{{ $price['compare_at']['formatted'] }}</s>
    @endif
    @if ($price['discount_percent'])
        <span class="ml-1 text-xs font-medium text-accent">-{{ $price['discount_percent'] }}%</span>
    @endif
@endif
