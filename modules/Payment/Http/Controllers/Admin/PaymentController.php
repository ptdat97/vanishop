<?php

declare(strict_types=1);

namespace Modules\Payment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Contracts\CapturesLater;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\StoreClock;

final class PaymentController
{
    public function home(): RedirectResponse
    {
        Gate::authorize('payments.view');

        return redirect()->route('admin.payment.payments.index');
    }

    public function index(Request $request, OrderReader $orders, GatewayRegistry $gateways): Response
    {
        Gate::authorize('payments.view');
        $status = $request->query('status');
        $payments = Payment::query()->when(is_string($status) && $status !== '', fn ($query) => $query->where('status', $status))->orderByDesc('id')->limit(100)->get();
        $refunds = Refund::query()->whereIn('payment_id', Payment::query()->select('id'))->where('status', 'requested')->orderBy('id')->get();

        return Inertia::render('Payment::Payments/Index', [
            'baseUrl' => route('admin.payment.payments.index'),
            'status' => $status,
            'statuses' => array_column(PaymentStatus::cases(), 'value'),
            'payments' => $payments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'order_number' => $orders->find($payment->order_id)?->number,
                'gateway' => $gateways->get($payment->gateway_code)?->label() ?? $payment->gateway_code,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'refunded_amount' => $payment->refunded_amount,
                'created_at' => StoreClock::format($payment->created_at),
                'expires_at' => StoreClock::format($payment->expires_at),
                'can_confirm' => in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired], true)
                    && ($gateways->get($payment->gateway_code)?->capabilities()->manualConfirmation ?? false),
                'can_capture' => $payment->status === PaymentStatus::Authorized && $gateways->get($payment->gateway_code) instanceof CapturesLater,
                'can_refund' => $payment->status->hasCollected() && $payment->refunded_amount < $payment->amount,
            ])->all(),
            'refunds' => $refunds->map(fn (Refund $refund): array => [
                'id' => $refund->id, 'payment_id' => $refund->payment_id, 'amount' => $refund->amount, 'reason' => $refund->reason,
                'created_at' => StoreClock::format($refund->created_at),
            ])->all(),
            'can' => [
                'confirm' => Gate::allows('payments.confirm'),
                'refund' => Gate::allows('payments.refund'),
            ],
        ]);
    }

    public function confirm(Payment $payment, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.confirm');
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->confirmManually($payment->id, $data['note']);

        return back()->with('success', __('payment::messages.confirmed'));
    }

    /**
     * Thu khoản đang giữ tiền (cổng CapturesLater) — khi chưa tự thu lúc vận đơn rời kho hoặc `capture_on = manual`.
     */
    public function capture(Payment $payment, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.confirm');
        $payments->captureAuthorized($payment->id, 'staff');

        return back()->with('success', __('payment::messages.captured'));
    }

    public function refund(Payment $payment, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund');
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:64', Rule::unique('refunds', 'idempotency_key')],
        ]);
        $payments->refund($payment->id, Money::of((int) $data['amount'], $payment->currency_code), $data['reason'], 'admin:'.Str::lower($data['idempotency_key']));

        return back()->with('success', __('payment::messages.refund_created'));
    }

    public function completeRefund(Refund $refund, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund');
        abort_unless(Payment::query()->whereKey($refund->payment_id)->exists(), 404);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->completeManualRefund($refund->id, $data['note']);

        return back()->with('success', __('payment::messages.refund_completed'));
    }
}
