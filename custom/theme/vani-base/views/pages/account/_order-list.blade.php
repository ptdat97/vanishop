@if ($orders === [])
    <p class="text-sm text-slate-500">Chưa có đơn hàng.</p>
@else
    <ul class="divide-y divide-slate-200 border-y border-slate-200 text-sm">
        @foreach ($orders as $order)
            <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                <a href="{{ route('storefront.account.order', $order['id']) }}" class="font-medium underline">{{ $order['number'] }}</a>
                <span class="text-slate-500">{{ $order['placed_at'] }}</span>
                <span>{{ $order['status'] }}</span>
                <span class="font-medium">{{ $order['total']['formatted'] ?? '' }}</span>
            </li>
        @endforeach
    </ul>
@endif
