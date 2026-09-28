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
use Modules\Brand\Http\Controllers\BrandWorkspaceHome;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Identity\Contracts\Data\ScopeRef;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Domain\Money\Money;

final class PaymentController
{
    public function home(BrandWorkspaceHome $home): Response|RedirectResponse
    {
        Gate::authorize('payments.view');

        return $home->respond('admin.payment.payments.index', 'Thanh toán', 'Chọn brand để xem thanh toán và hoàn tiền.');
    }

    public function index(Brand $brand, Request $request, OrderReader $orders, GatewayRegistry $gateways): Response
    {
        Gate::authorize('payments.view', [ScopeRef::brand($brand->id)]);
        $status = $request->query('status');
        $payments = Payment::query()->when(is_string($status) && $status !== '', fn ($query) => $query->where('status', $status))->orderByDesc('id')->limit(100)->get();
        $refunds = Refund::query()->whereIn('payment_id', Payment::query()->select('id'))->where('status', 'requested')->orderBy('id')->get();

        return Inertia::render('Payment::Payments/Index', [
            'brand' => ['name' => $brand->name, 'slug' => $brand->slug],
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
                'created_at' => $payment->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'expires_at' => $payment->expires_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
                'can_confirm' => in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired], true)
                    && ($gateways->get($payment->gateway_code)?->capabilities()->manualConfirmation ?? false),
                'can_refund' => $payment->status->hasCollected() && $payment->refunded_amount < $payment->amount,
            ])->all(),
            'refunds' => $refunds->map(fn (Refund $refund): array => [
                'id' => $refund->id, 'payment_id' => $refund->payment_id, 'amount' => $refund->amount, 'reason' => $refund->reason,
                'created_at' => $refund->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i'),
            ])->all(),
            'can' => [
                'confirm' => Gate::allows('payments.confirm', [ScopeRef::brand($brand->id)]),
                'refund' => Gate::allows('payments.refund', [ScopeRef::brand($brand->id)]),
            ],
        ]);
    }

    public function confirm(Brand $brand, Payment $payment, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.confirm', [ScopeRef::brand($brand->id)]);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->confirmManually($payment->id, $data['note']);

        return back()->with('success', __('payment::messages.confirmed'));
    }

    public function refund(Brand $brand, Payment $payment, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund', [ScopeRef::brand($brand->id)]);
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:255'],
            'idempotency_key' => ['required', 'string', 'max:64', Rule::unique('refunds', 'idempotency_key')],
        ]);
        $payments->refund($payment->id, Money::of((int) $data['amount'], $payment->currency_code), $data['reason'], 'admin:'.Str::lower($data['idempotency_key']));

        return back()->with('success', __('payment::messages.refund_created'));
    }

    public function completeRefund(Brand $brand, Refund $refund, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund', [ScopeRef::brand($brand->id)]);
        abort_unless(Payment::query()->whereKey($refund->payment_id)->exists(), 404);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->completeManualRefund($refund->id, $data['note']);

        return back()->with('success', __('payment::messages.refund_completed'));
    }
}
