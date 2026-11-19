{{-- Khách xác nhận đã nhận hàng (§3.11-1): đơn đã giao (hoặc đang giao đã lâu). Không cần JS. --}}
@php($receipt = $order['receipt'] ?? [])
@if ($receipt['confirmed_at'] ?? null)
    <p class="mt-6 rounded-[var(--radius-theme)] bg-emerald-50 px-4 py-2 text-sm text-emerald-800" data-order-received>
        {{ __('theme::order.received_confirmed') }} @if ($receipt['returns_waived'] ?? false){{ __('theme::order.returns_waived_order') }}@endif
    </p>
@elseif ($receipt['can_confirm'] ?? false)
    <form action="{{ vani_route('storefront.order.received', $order['id']) }}" method="post" class="mt-6 flex flex-wrap items-center gap-3 rounded-[var(--radius-theme)] border border-slate-200 p-4 text-sm" data-order-receipt>
        @csrf
        <p class="w-full">{{ __('theme::order.received_question') }}</p>
        <label class="flex items-center gap-2"><input type="checkbox" name="waive_returns" value="1"> {{ __('theme::order.returns_not_needed') }}</label>
        <button type="submit" class="rounded-[var(--radius-theme)] bg-primary px-4 py-1.5 font-medium text-white">{{ __('theme::order.confirm_received') }}</button>
    </form>
@endif
