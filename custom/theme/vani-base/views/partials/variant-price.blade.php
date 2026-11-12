{{-- Giá của một biến thể (`colors[].variants[].price`): JS thay vào chỗ giá khi khách chọn size. --}}
<span class="font-semibold text-primary" data-price>{{ $price['amount']['formatted'] }}</span>
@if ($price['compare_at'])
    <s class="ml-1 text-sm text-slate-400">{{ $price['compare_at']['formatted'] }}</s>
@endif
@if ($price['discount_percent'])
    <span class="ml-1 text-xs font-medium text-accent">-{{ $price['discount_percent'] }}%</span>
@endif
