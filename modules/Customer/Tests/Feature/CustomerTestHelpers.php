<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Application\CustomerService;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Support\Phones;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

final class CustomerTestHelpers
{
    public const API = '/api/storefront/v1';

    /** Header chung của Storefront API (không còn header kênh — ADR-028). */
    public const CHANNEL = [];

    public static function fakeOtp(): void
    {
        FakeOtpSender::$codes = [];
        FakeOtpSender::$available = true;
        app(Extensions::class)->tag([FakeOtpSender::class], OtpSender::TAG);
    }

    public const PASSWORD = 'mat-khau-123';

    /**
     * Tài khoản đã đăng ký (email đã xác minh) có SĐT `$phone` — nhận hồ sơ khách vãng lai cùng SĐT nếu có, như khách đã đặt
     * hàng rồi đăng ký. Email mặc định suy ra từ SĐT.
     */
    public static function account(string $phone = '0912345678', ?string $email = null): Customer
    {
        $customer = app(CustomerService::class)->findOrCreateByPhone(Phones::fromString($phone)->e164);
        $customer->update([
            'email' => $customer->email ?? $email ?? self::email($phone), 'password' => self::PASSWORD,
            'registered_at' => $customer->registered_at ?? now(), 'email_verified_at' => now(),
        ]);

        return $customer->refresh();
    }

    public static function email(string $phone = '0912345678'): string
    {
        return 'khach'.preg_replace('/\D/', '', $phone).'@example.test';
    }

    /**
     * Đăng nhập email + mật khẩu qua Storefront API (tài khoản tạo bằng `account()`), trả token Bearer.
     *
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    public static function login(TestCase $test, string $phone = '0912345678', array $extra = [], array $headers = []): string
    {
        $customer = self::account($phone);

        return $test->postJson(self::API.'/auth/login', ['email' => $customer->email, 'password' => self::PASSWORD, ...$extra], [...self::CHANNEL, ...$headers])
            ->assertOk()->json('meta.token');
    }

    /**
     * Đăng nhập trên storefront native (phiên web).
     */
    public static function webLogin(TestCase $test, string $phone = '0912345678'): Customer
    {
        $customer = self::account($phone);
        $test->post('/tai-khoan/dang-nhap', ['email' => $customer->email, 'password' => self::PASSWORD])->assertRedirect();

        return $customer;
    }

    /**
     * Link trong email gần nhất gửi tới `$email` (mailer `array` khi test) — link xác minh đăng ký hoặc đặt lại mật khẩu.
     */
    public static function mailedLink(string $email): string
    {
        $messages = array_filter(
            iterator_to_array(Mail::mailer('array')->getSymfonyTransport()->messages()),
            fn (SentMessage $sent): bool => in_array($email, array_map(fn ($address): string => $address->getAddress(), $sent->getOriginalMessage()->getTo()), true),
        );
        $body = (string) end($messages)?->getOriginalMessage()->getTextBody();
        preg_match('#https?://\S+#', $body, $match);

        return $match[0] ?? throw new \RuntimeException("Không có link trong email gửi tới {$email}.");
    }

    /** Token (64 ký tự) ở cuối link email. */
    public static function mailedToken(string $email): string
    {
        return basename((string) parse_url(self::mailedLink($email), PHP_URL_PATH));
    }

    /**
     * @return array<string, string>
     */
    public static function auth(string $token): array
    {
        return [...self::CHANNEL, 'Authorization' => "Bearer {$token}"];
    }

    /**
     * Khách vãng lai đặt một đơn COD, trả số đơn.
     *
     * @param  array<string, mixed>  $contact
     */
    public static function guestOrder(TestCase $test, int $variantId, string $key, array $contact = []): string
    {
        $created = $test->postJson(self::API.'/carts', [], self::CHANNEL)->assertCreated();
        $headers = [...self::CHANNEL, 'X-Vani-Cart-Token' => $created->json('meta.token')];
        $test->postJson(self::API."/carts/{$created->json('data.id')}/lines", ['variant_id' => $variantId, 'quantity' => 1], $headers)->assertOk();

        return $test->postJson(self::API."/checkout/{$created->json('data.id')}/orders", C::orderPayload(['expected_total' => 330_000, 'contact' => $contact]), [...$headers, 'Idempotency-Key' => $key])
            ->assertCreated()->json('data.number');
    }
}
