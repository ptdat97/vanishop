{{-- Khách xác nhận đã nhận hàng (§3.11-1): đơn đã giao (hoặc đang giao đã lâu). Không cần JS. --}}
@php($receipt = $order['receipt'] ?? [])
@if ($receipt['confirmed_at'] ?? null)
    <p class="mt-6 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800" data-order-received>
        {{ __('Bạn đã xác nhận nhận hàng.') }} @if ($receipt['returns_waived'] ?? false){{ __('Đơn này không đổi trả.') }}@endif
    </p>
@elseif ($receipt['can_confirm'] ?? false)
    <form action="{{ vani_route('storefront.order.received', $order['id']) }}" method="post" class="mt-6 flex flex-wrap items-center gap-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-order-receipt>
        @csrf
        <p class="w-full">{{ __('Bạn đã nhận được hàng?') }}</p>
        <label class="flex items-center gap-2"><input type="checkbox" name="waive_returns" value="1"> {{ __('Tôi hài lòng, không cần đổi trả đơn này') }}</label>
        <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-1.5 font-medium text-white">{{ __('Đã nhận hàng') }}</button>
    </form>
@endif
