<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Application\OrderPresenter;

/**
 * Tài khoản khách trên native storefront: đăng nhập OTP (token trong phiên), đơn hàng, địa chỉ. Cùng contract
 * với Storefront API (CustomerSessions, CustomerOrders).
 */
final class AccountController
{
    private const TOKEN = 'vani.customer_token';

    private const PENDING_PHONE = 'vani.login_phone';

    public function __construct(
        private readonly CustomerSessions $sessions,
        private readonly CustomerOrders $orders,
        private readonly OrderPresenter $presenter,
    ) {}

    public function login(Request $request): View|RedirectResponse
    {
        if ($this->customer($request) !== null) {
            return redirect()->route('storefront.account');
        }

        return view('theme::pages.account.login', ['phone' => $request->session()->get(self::PENDING_PHONE)]);
    }

    public function requestOtp(Request $request): RedirectResponse
    {
        $data = $request->validate(['phone' => ['required', 'string', 'max:20']]);
        $this->sessions->requestLoginOtp($data['phone'], (string) $request->ip());
        $request->session()->put(self::PENDING_PHONE, $data['phone']);

        return redirect()->route('storefront.account.login')->with('status', __('storefront::messages.otp_sent'));
    }

    public function verify(Request $request, NativeCart $cart): RedirectResponse
    {
        $data = $request->validate(['code' => ['required', 'string', 'max:10']]);
        $phone = (string) $request->session()->get(self::PENDING_PHONE, '');
        if ($phone === '') {
            return redirect()->route('storefront.account.login');
        }

        $session = $this->sessions->loginWithOtp($phone, $data['code'], 'web');
        $request->session()->regenerate();
        $request->session()->forget(self::PENDING_PHONE);
        $request->session()->put(self::TOKEN, $session['token']);
        $cart->attachGuestCartTo($session['customer']->id);

        return redirect()->intended(route('storefront.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $this->sessions->logout((string) $request->session()->get(self::TOKEN, ''));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('storefront.home');
    }

    public function dashboard(Request $request): View
    {
        $customer = $this->required($request);

        return view('theme::pages.account.dashboard', [
            'customer' => $customer,
            'recentOrders' => $this->summaries($this->orders->ofCustomer($customer->id, 1, 5)['data']),
        ]);
    }

    public function orders(Request $request): View
    {
        $customer = $this->required($request);
        $page = max(1, (int) $request->query('page', 1));
        $result = $this->orders->ofCustomer($customer->id, $page, 10);

        return view('theme::pages.account.orders', [
            'orders' => $this->summaries($result['data']),
            'page' => $page,
            'pages' => (int) ceil($result['total'] / 10),
        ]);
    }

    public function order(Request $request, string $order): View
    {
        $customer = $this->required($request);
        $detail = $this->orders->showForCustomer($customer->id, $order);
        abort_if($detail === null, 404);

        return view('theme::pages.account.order', ['order' => $this->presenter->present($detail)]);
    }

    public function cancelOrder(Request $request, string $order): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $this->orders->cancelForCustomer($customer->id, $order, $data['reason']);

        return redirect()->route('storefront.account.order', $order)->with('status', __('storefront::messages.order_cancelled'));
    }

    public function addresses(Request $request): View
    {
        $customer = $this->required($request);

        return view('theme::pages.account.addresses', ['addresses' => $this->sessions->addresses($customer->id)]);
    }

    /**
     * @param  list<OrderDetail>  $orders
     * @return list<array<string, mixed>>
     */
    private function summaries(array $orders): array
    {
        return array_map(fn (OrderDetail $order): array => [
            'id' => $order->publicId, 'number' => $order->number, 'placed_at' => $order->placedAt,
            'status' => $order->customerStatus['label'], 'total' => $this->presenter->present($order)['total'],
        ], $orders);
    }

    private function customer(Request $request): ?CustomerData
    {
        $token = (string) $request->session()->get(self::TOKEN, '');

        return $token === '' ? null : $this->sessions->authenticate($token);
    }

    private function required(Request $request): CustomerData
    {
        return $this->customer($request) ?? abort(401);
    }
}
