<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Requests\ForgotPasswordRequest;
use Modules\Customer\Contracts\Requests\LoginRequest;
use Modules\Customer\Contracts\Requests\RegisterOtpRequest;
use Modules\Customer\Contracts\Requests\RegisterRequest;
use Modules\Customer\Contracts\Requests\ResetPasswordRequest;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Shared\Support\StoreClock;
use Modules\Storefront\Application\NativeCustomerSignIn;
use Modules\Storefront\Application\OrderPresenter;
use Modules\Storefront\Application\ReturnFormPresenter;
use Modules\Storefront\Contracts\CustomerSignIn;
use Modules\Storefront\Http\Requests\CancelOrderRequest;

/**
 * Tài khoản khách trên native storefront: đăng nhập bằng email + mật khẩu (mặc định của Core, 0.5.12; token trong phiên), đăng
 * ký qua link xác minh email, quên mật khẩu, đơn hàng, địa chỉ. Cùng contract với Storefront API (CustomerSessions,
 * CustomerOrders). Phương thức khác (OTP của `vani.phone-otp-login`, mạng xã hội) gắn form vào slot `vani.storefront.account.login`.
 */
final class AccountController
{
    private const REGISTER_OTP_PHONE = 'vani.register_otp_phone';

    private const TOKEN = NativeCustomerSignIn::TOKEN;

    public function __construct(
        private readonly CustomerSessions $sessions,
        private readonly CustomerOrders $orders,
        private readonly OrderPresenter $presenter,
        private readonly CustomerSignIn $signIn,
    ) {}

    public function login(Request $request): View|RedirectResponse
    {
        if ($this->customer($request) !== null) {
            return redirect()->to(LocalizedUrls::route('storefront.account'));
        }

        return view('theme::pages.account.login');
    }

    /**
     * Email + mật khẩu → đăng nhập, gộp giỏ vãng lai. JSON (`window.vani.auth.login`): `{signed_in, message, redirect,
     * csrf_token}` — phiên được cấp lại nên JS phải dùng `csrf_token` mới cho request sau.
     */
    public function authenticate(LoginRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $session = $this->sessions->loginWithPassword($data['email'], $data['password'], (string) $request->ip(), 'web');

        return $this->signedIn($request, $session['token'], $session['customer']->id);
    }

    public function register(Request $request): View|RedirectResponse
    {
        if ($this->customer($request) !== null) {
            return redirect()->to(LocalizedUrls::route('storefront.account'));
        }

        return view('theme::pages.account.register', ['sentTo' => $request->session()->get('vani.register_sent_to')]);
    }

    /**
     * Đăng ký theo cài đặt xác minh của cửa hàng (0.5.114): tạo ngay → đăng nhập; link email → thông báo "kiểm tra email";
     * mã OTP → trang nhập mã.
     */
    public function storeRegistration(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $result = $this->sessions->register($data['email'], $data['password'], $data['full_name'], $data['phone'], (bool) ($data['marketing_email'] ?? false), (string) $request->ip(), 'web');

        if ($result['session'] !== null) {
            return redirect()->to($this->signIn->signIn($request, $result['session']['token'], $result['session']['customer']->id))->with('status', __('storefront::messages.registered'));
        }

        return match ($result['status']) {
            'verify_otp' => redirect()->to(LocalizedUrls::route('storefront.account.register.otp'))->with(self::REGISTER_OTP_PHONE, $result['phone']),
            default => redirect()->to(LocalizedUrls::route('storefront.account.register'))->with('vani.register_sent_to', $result['email']),
        };
    }

    /** Trang nhập mã OTP đăng ký (0.5.114). SĐT lấy từ bước đăng ký; không có thì về trang đăng ký. */
    public function registerOtp(Request $request): View|RedirectResponse
    {
        $phone = $request->session()->get(self::REGISTER_OTP_PHONE);
        if (! is_string($phone)) {
            return redirect()->to(LocalizedUrls::route('storefront.account.register'));
        }
        $request->session()->keep([self::REGISTER_OTP_PHONE]);

        return view('theme::pages.account.register-otp', ['phone' => $phone]);
    }

    public function verifyRegistrationOtp(RegisterOtpRequest $request): RedirectResponse
    {
        $phone = $request->session()->get(self::REGISTER_OTP_PHONE);
        if (! is_string($phone)) {
            return redirect()->to(LocalizedUrls::route('storefront.account.register'));
        }
        $data = $request->validated();
        try {
            $session = $this->sessions->verifyRegistrationOtp($phone, $data['code'], 'web');
        } catch (CustomerRejected $exception) {
            $request->session()->keep([self::REGISTER_OTP_PHONE]);

            return back()->withErrors(['code' => $exception->getMessage()]);
        }
        $request->session()->forget(self::REGISTER_OTP_PHONE);

        return redirect()->to($this->signIn->signIn($request, $session['token'], $session['customer']->id))->with('status', __('storefront::messages.registered'));
    }

    /**
     * Link trong email xác minh → tạo tài khoản và đăng nhập. Link sai/hết hạn → về trang đăng ký kèm lỗi.
     */
    public function verifyRegistration(Request $request, string $token): RedirectResponse
    {
        try {
            $session = $this->sessions->verifyRegistration($token, 'web');
        } catch (CustomerRejected $exception) {
            return redirect()->to(LocalizedUrls::route('storefront.account.register'))->withErrors(['business' => $exception->getMessage()]);
        }
        $redirect = $this->signIn->signIn($request, $session['token'], $session['customer']->id);

        return redirect()->to($redirect)->with('status', __('storefront::messages.registered'));
    }

    public function forgotPassword(): View
    {
        return view('theme::pages.account.forgot-password');
    }

    /**
     * Luôn báo "đã gửi nếu email có tài khoản" — không lộ email nào là khách của cửa hàng.
     */
    public function sendPasswordReset(ForgotPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->sessions->requestPasswordReset($data['email']);

        return redirect()->to(LocalizedUrls::route('storefront.account.password.forgot'))->with('status', __('storefront::messages.password_reset_sent'));
    }

    public function resetPasswordForm(string $token): View
    {
        return view('theme::pages.account.reset-password', ['token' => $token]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->sessions->resetPassword($data['token'], $data['password']);

        return redirect()->to(LocalizedUrls::route('storefront.account.login'))->with('status', __('storefront::messages.password_reset_done'));
    }

    public function logout(Request $request): RedirectResponse|JsonResponse
    {
        $this->sessions->logout((string) $request->session()->get(self::TOKEN, ''));
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['signed_in' => false, 'redirect' => LocalizedUrls::route('storefront.home'), 'csrf_token' => $request->session()->token()]);
        }

        return redirect()->to(LocalizedUrls::route('storefront.home'));
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

    public function order(Request $request, string $order, ReturnFormPresenter $returnForm): View
    {
        $customer = $this->required($request);
        $detail = $this->orders->showForCustomer($customer->id, $order);
        abort_if($detail === null, 404);
        $presented = $this->presenter->present($detail);

        return view('theme::pages.account.order', [
            'order' => [...$presented, 'placed_at_label' => StoreClock::format($presented['placed_at'])],
            'returnForm' => $returnForm->present($presented),
            'cancellationReasons' => $presented['can_cancel'] ? $this->orders->cancellationReasons() : [],
        ]);
    }

    public function cancelOrder(CancelOrderRequest $request, string $order): RedirectResponse
    {
        $customer = $this->required($request);
        $data = $request->validated();
        $this->orders->cancelForCustomer($customer->id, $order, $data['reason'] ?? '', $data['reason_code'] ?? null);

        return redirect()->to(LocalizedUrls::route('storefront.account.order', $order))->with('status', __('storefront::messages.order_cancelled'));
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
            'id' => $order->publicId, 'number' => $order->number, 'placed_at' => $order->placedAt, 'placed_at_label' => StoreClock::format($order->placedAt),
            'status' => $order->customerStatus['label'], 'total' => $this->presenter->present($order)['total'],
        ], $orders);
    }

    private function signedIn(Request $request, string $token, int $customerId): RedirectResponse|JsonResponse
    {
        $redirect = $this->signIn->signIn($request, $token, $customerId);
        if ($request->expectsJson()) {
            return response()->json([
                'signed_in' => true,
                'message' => __('storefront::messages.signed_in'),
                'redirect' => $redirect,
                'csrf_token' => $request->session()->token(),
            ]);
        }

        return redirect()->to($redirect);
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
