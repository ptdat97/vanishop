<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\SocialLogin;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Requests\ForgotPasswordRequest;
use Modules\Customer\Contracts\Requests\LoginRequest;
use Modules\Customer\Contracts\Requests\RegisterRequest;
use Modules\Customer\Contracts\Requests\ResetPasswordRequest;

/**
 * Đăng nhập khách bằng email + mật khẩu (mặc định của Core, 0.5.12): đăng ký → link xác minh qua email → tài khoản + phiên;
 * quên mật khẩu qua link email. Trả `meta.token` (Bearer, chỉ một lần). Gửi kèm `cart_id` + `X-Vani-Cart-Token` để gộp giỏ
 * vãng lai vào giỏ của khách. OTP theo SĐT là plugin (`vani.phone-otp-login`), mạng xã hội qua `AuthProvider`.
 */
final class AuthController
{
    /** Cùng contract với storefront Blade (L45): đăng ký, đăng nhập, mật khẩu, xác minh email đi một đường. */
    public function __construct(private readonly CustomerSessions $auth) {}

    /**
     * Gửi link xác minh tới email (202); tài khoản chỉ tạo khi bấm link (`POST /auth/register/verify`).
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $this->auth->register($data['email'], $data['password'], $data['full_name'])], 202);
    }

    /**
     * Token trong link xác minh → tài khoản + phiên.
     */
    public function verifyRegistration(Request $request, Carts $carts): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'size:64'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ]);
        $session = $this->auth->verifyRegistration($data['token'], $data['device'] ?? null);

        return $this->respond($session, $request, $carts, $data['cart_id'] ?? null);
    }

    /**
     * Xác minh email sau khi đổi email trong hồ sơ (0.5.32): token từ link trong email. Không tạo phiên đăng nhập.
     */
    public function verifyEmail(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'size:64']]);
        $customer = $this->auth->verifyEmail($data['token']) ?? throw CustomerRejected::emailLinkInvalid();

        return response()->json(['data' => ['email' => $customer->email, 'email_verified' => $customer->emailVerified]]);
    }

    public function login(LoginRequest $request, Carts $carts): JsonResponse
    {
        $data = $request->validated();
        $session = $this->auth->loginWithPassword($data['email'], $data['password'], (string) $request->ip(), $data['device'] ?? null);

        return $this->respond($session, $request, $carts, $data['cart_id'] ?? null);
    }

    /**
     * Luôn 202 (không báo email có phải tài khoản hay không).
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->auth->requestPasswordReset($data['email']);

        return response()->json(['data' => ['email' => AuthService::normalizeEmail($data['email'])]], 202);
    }

    /**
     * Mật khẩu mới bằng token trong link email; mọi phiên cũ bị thu hồi — đăng nhập lại bằng mật khẩu mới.
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->auth->resetPassword($data['token'], $data['password']);

        return response()->json(null, 204);
    }

    /**
     * Phương thức đăng nhập bên ngoài đang bật (AuthProvider của plugin).
     */
    public function socialProviders(SocialLogin $social): JsonResponse
    {
        return response()->json(['data' => $social->providers()]);
    }

    /**
     * Bắt đầu: trả URL chuyển sang nhà cung cấp + `state` (client giữ lại, gửi kèm khi hoàn tất).
     */
    public function socialStart(Request $request, string $provider, SocialLogin $social): JsonResponse
    {
        $data = $request->validate(['redirect_uri' => ['required', 'url', 'max:500']]);

        return response()->json(['data' => $social->begin($provider, $data['redirect_uri'])]);
    }

    /**
     * Hoàn tất: tham số nhà cung cấp trả về (vd. `code`) + `state` → token như đăng nhập email.
     */
    public function socialComplete(Request $request, string $provider, SocialLogin $social, Carts $carts): JsonResponse
    {
        $data = $request->validate([
            'state' => ['required', 'string', 'size:40'],
            'params' => ['required', 'array', 'max:10'],
            'params.*' => ['nullable', 'string', 'max:2000'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ]);
        $session = $social->complete($provider, $data['state'], array_map('strval', array_filter($data['params'], fn (mixed $value): bool => $value !== null)), $data['device'] ?? null);

        return $this->respond($session, $request, $carts, $data['cart_id'] ?? null);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->auth->logout((string) $request->bearerToken());

        return response()->json(null, 204);
    }

    /**
     * @param  array{customer: CustomerData, token: string}  $session
     */
    private function respond(array $session, Request $request, Carts $carts, ?string $cartId): JsonResponse
    {
        $meta = ['token' => $session['token'], 'token_type' => 'Bearer'];
        if ($cartId !== null) {
            $cart = $carts->attachToCustomer(new CartKey($cartId, (string) $request->header('X-Vani-Cart-Token', '')), $session['customer']->id);
            $meta['cart_id'] = $cart->id;
        }

        return response()->json(['data' => $session['customer']->toArray(), 'meta' => $meta]);
    }
}
