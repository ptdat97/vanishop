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
use Modules\Extension\Contracts\AdminScreen;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Application\AdminQueries;
use Modules\Payment\Application\GatewayRegistry;
use Modules\Payment\Application\PaymentService;
use Modules\Payment\Application\ReconciliationLedger;
use Modules\Payment\Contracts\CapturesLater;
use Modules\Payment\Domain\PaymentStatus;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Payment\Persistence\Models\Refund;
use Modules\Shared\Domain\Money\Money;
use Modules\Shared\Support\StoreClock;

final class PaymentController
{
    public function __construct(private readonly AdminQueries $queries) {}

    /** Số dòng mỗi trang (mặc định là giá trị đầu) — như danh sách đơn hàng. */
    private const PER_PAGE_OPTIONS = [30, 50, 100];

    public function home(): RedirectResponse
    {
        Gate::authorize('payments.view');

        return redirect()->route('admin.payment.payments.index');
    }

    public function index(Request $request, OrderReader $orders, GatewayRegistry $gateways, PaymentService $service, AdminScreen $screen): Response
    {
        Gate::authorize('payments.view');
        $extensionFilters = array_filter((array) $request->query('ext', []), 'is_string');
        $extensionIds = $screen->filterIds('payment', $extensionFilters);
        $status = $request->query('status');
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->query('per_page') : self::PER_PAGE_OPTIONS[0];
        $page = $this->queries->payments(is_string($status) ? $status : '', $search === '' ? null : ($orders->findByNumber($search)->id ?? 0), $extensionIds, $perPage);
        $payments = collect($page->items());
        $numbers = $orders->numbers($payments->pluck('order_id')->map(fn ($id): int => (int) $id)->all());
        $refunds = $this->queries->refundsAwaitingPayout();

        return Inertia::render('Payment::Payments/Index', [
            'baseUrl' => route('admin.payment.payments.index'),
            'labels' => __('payment::labels'),
            'status' => $status,
            'search' => $search,
            'statuses' => array_column(PaymentStatus::cases(), 'value'),
            'payments' => $payments->map(fn (Payment $payment): array => [
                'id' => $payment->id,
                'order_number' => $numbers[$payment->order_id] ?? null,
                'order_url' => route('admin.orders.orders.show', ['order' => $payment->order_id]),
                'gateway' => $gateways->get($payment->gateway_code)?->label() ?? $payment->gateway_code,
                'status' => $payment->status->value,
                'amount' => $payment->amount,
                'refunded_amount' => $payment->refunded_amount,
                'created_at' => StoreClock::format($payment->created_at),
                'expires_at' => StoreClock::format($payment->expires_at),
                'can_confirm' => in_array($payment->status, [PaymentStatus::Pending, PaymentStatus::Failed, PaymentStatus::Expired], true)
                    && ($gateways->get($payment->gateway_code)?->capabilities()->manualConfirmation ?? false),
                'can_collect' => $service->canCollectCodManually($payment),
                'can_capture' => $payment->status === PaymentStatus::Authorized && $gateways->get($payment->gateway_code) instanceof CapturesLater,
                'can_refund' => $payment->status->hasCollected() && $payment->refunded_amount < $payment->amount,
            ])->all(),
            'pagination' => ['page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'from' => $page->firstItem(), 'to' => $page->lastItem(), 'total' => $page->total(), 'per_page' => $page->perPage()],
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            // EP v3 X5: kênh chuyển trả của plugin; dòng đối soát lệch chờ xử lý.
            'payouts' => array_values(array_map(fn ($payout): array => ['code' => $payout->code(), 'label' => $payout->label()], $service->payouts())),
            'reconciliation' => app(ReconciliationLedger::class)->open(),
            'refunds' => $refunds->map(fn (Refund $refund): array => [
                'id' => $refund->id, 'payment_id' => $refund->payment_id, 'amount' => $refund->amount, 'reason' => $refund->reason, 'paying_out' => $refund->status === 'paying_out',
                'created_at' => StoreClock::format($refund->created_at),
            ])->all(),
            'can' => [
                'confirm' => Gate::allows('payments.confirm'),
                'refund' => Gate::allows('payments.refund'),
            ],
            // Cột/lọc/thao tác hàng loạt/công cụ tải file của plugin (EP v3 X1) — vd. nhập sao kê ngân hàng.
            'extensions' => $screen->listProps('payment', $payments->pluck('id')->map(fn ($id): int => (int) $id)->all(), $extensionFilters),
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
     * Ghi nhận đã thu tiền khi giao (COD) — shop tự giao hoặc hãng không báo giao thành công.
     */
    public function collect(Payment $payment, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.confirm');
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->collectCodManually($payment->id, $data['note']);

        return back()->with('success', __('payment::messages.cod_collected'));
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

    /**
     * Chuyển trả khoản hoàn qua kênh của plugin (EP v3 X5).
     */
    public function payout(Refund $refund, string $channel, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund');
        $label = ($payments->payouts()[$channel] ?? abort(404))->label();
        $reference = $payments->payoutRefund($refund->id, $channel);

        return back()->with('success', __('payment::messages.payout_done', ['channel' => $label, 'reference' => $reference]));
    }

    public function resolveReconciliation(int $entry, Request $request, ReconciliationLedger $ledger): RedirectResponse
    {
        Gate::authorize('payments.confirm');
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $ledger->resolve($entry, $data['note']);

        return back()->with('success', __('payment::messages.reconciliation_resolved'));
    }

    public function completeRefund(Refund $refund, Request $request, PaymentService $payments): RedirectResponse
    {
        Gate::authorize('payments.refund');
        abort_unless($this->queries->paymentExists((int) $refund->payment_id), 404);
        $data = $request->validate(['note' => ['required', 'string', 'max:255']]);
        $payments->completeManualRefund($refund->id, $data['note']);

        return back()->with('success', __('payment::messages.refund_completed'));
    }
}
