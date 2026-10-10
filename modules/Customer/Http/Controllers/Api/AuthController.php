<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Application\OtpService;
use Modules\Customer\Application\SocialLogin;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Shared\Support\Phones;

/**
 * Đăng nhập khách: OTP (mặc định) hoặc mật khẩu. Trả `meta.token` (Bearer, chỉ một lần). Gửi kèm
 * `cart_id` + `X-Vani-Cart-Token` để gộp giỏ vãng lai vào giỏ của khách.
 */
final class AuthController
{
    public function __construct(private readonly AuthService $auth) {}

    public function requestOtp(Request $request, OtpService $otp): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'purpose' => ['nullable', Rule::enum(OtpPurpose::class)],
        ]);

        $result = $otp->request($this->phone($data['phone']), OtpPurpose::from($data['purpose'] ?? OtpPurpose::Login->value), (string) $request->ip());

        return response()->json(['data' => $result], 202);
    }

    public function verifyOtp(Request $request, Carts $carts): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'code' => ['required', 'string', 'max:10'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ]);
        $session = $this->auth->loginWithOtp($this->phone($data['phone']), $data['code'], $data['device'] ?? null);

        return $this->respond($session, $request, $carts, $data['cart_id'] ?? null);
    }

    public function login(Request $request, Carts $carts): JsonResponse
    {
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'password' => ['required', 'string', 'max:200'],
            'device' => ['nullable', 'string', 'max:64'],
            'cart_id' => ['nullable', 'string', 'size:26'],
        ]);
        $session = $this->auth->loginWithPassword($this->phone($data['phone']), $data['password'], (string) $request->ip(), $data['device'] ?? null);

        return $this->respond($session, $request, $carts, $data['cart_id'] ?? null);
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
     * Hoàn tất: tham số nhà cung cấp trả về (vd. `code`) + `state` → token như đăng nhập OTP.
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
     * @param  array{customer: CustomerData, token: string, customer_id: int}  $session
     */
    private function respond(array $session, Request $request, Carts $carts, ?string $cartId): JsonResponse
    {
        $meta = ['token' => $session['token'], 'token_type' => 'Bearer'];
        if ($cartId !== null) {
            $cart = $carts->attachToCustomer(new CartKey($cartId, (string) $request->header('X-Vani-Cart-Token', '')), $session['customer_id']);
            $meta['cart_id'] = $cart->id;
        }

        return response()->json(['data' => $session['customer']->toArray(), 'meta' => $meta]);
    }

    private function phone(string $input): string
    {
        return Phones::parse($input)?->e164 ?? throw ValidationException::withMessages(['phone' => __('Số điện thoại không hợp lệ.')]);
    }
}
