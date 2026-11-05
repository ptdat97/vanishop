<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\Session\Session;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Payment\Contracts\Data\PaymentInitiation;
use Modules\Payment\Contracts\Payments;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\OrderPresenter;
use Modules\Storefront\Application\PaymentPresenter;
use Modules\Storefront\Application\ReturnFormPresenter;

/**
 * Trang cảm ơn / chi tiết đơn vừa đặt: token xem đơn giữ trong phiên (thay header X-Vani-Order-Token của API).
 */
final class OrderController
{
    public function show(Request $request, string $order, Session $session, CustomerOrders $orders, OrderPresenter $presenter, Payments $payments, PaymentPresenter $paymentPresenter, ReturnFormPresenter $returnForm): View
    {
        $stored = (array) $session->get("vani.orders.{$order}", []);
        $customer = $request->attributes->get('customer');
        $detail = $orders->show($order, (string) ($stored['token'] ?? ''))
            ?? ($customer === null ? null : $orders->showForCustomer((int) $customer->id, $order));
        abort_if($detail === null, 404);

        // Khoản thanh toán mới nhất của đơn (IPN có thể đã về; khách có thể đã thanh toán lại) — không dùng bản chụp lúc đặt.
        $payment = $payments->latestForOrder($detail->id);

        $presented = $presenter->present($detail);

        return view('theme::pages.order', [
            'order' => $presented,
            'returnForm' => $returnForm->present($presented),
            'payment' => $payment === null ? ($stored['payment'] ?? null) : $paymentPresenter->present($payment),
        ]);
    }

    /**
     * Khách xác nhận đã nhận hàng (§3.11-1), tuỳ chọn "không đổi trả đơn này". Quyền như trang đơn: token trong phiên hoặc
     * khách đăng nhập là chủ đơn.
     */
    public function received(Request $request, string $order, Session $session, CustomerOrders $orders): RedirectResponse
    {
        $waive = $request->boolean('waive_returns');
        $stored = (array) $session->get("vani.orders.{$order}", []);
        $customer = $request->attributes->get('customer');
        if (($stored['token'] ?? '') !== '' && $orders->show($order, (string) $stored['token']) !== null) {
            $orders->confirmReceived($order, (string) $stored['token'], $waive);
        } elseif ($customer !== null) {
            $orders->confirmReceivedForCustomer((int) $customer->id, $order, $waive);
        } else {
            abort(404);
        }

        return back()->with('status', __('storefront::messages.order_received'));
    }

    /**
     * Thanh toán lại (§3.11-2): đơn online chưa trả, còn hạn. Cổng chuyển hướng → sang trang cổng; QR/hướng dẫn → về trang đơn.
     */
    public function pay(Request $request, string $order, Session $session, CustomerOrders $orders, Payments $payments): RedirectResponse
    {
        $stored = (array) $session->get("vani.orders.{$order}", []);
        $customer = $request->attributes->get('customer');
        $detail = $orders->show($order, (string) ($stored['token'] ?? ''))
            ?? ($customer === null ? null : $orders->showForCustomer((int) $customer->id, $order));
        abort_if($detail === null, 404);

        $payment = $payments->retry($detail->id);
        if ($payment->action?->type === PaymentInitiation::REDIRECT && $payment->action->url !== null) {
            return redirect()->away($payment->action->url);
        }

        return redirect()->to(LocalizedUrls::route('storefront.order', $order))->with('status', $payment->actionError ?? __('storefront::messages.payment_restarted'));
    }
}
