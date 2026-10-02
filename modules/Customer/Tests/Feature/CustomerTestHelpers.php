<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature;

use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Tests\Feature\Fixtures\FakeOtpSender;
use Modules\Extension\Contracts\Extensions;
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

    /**
     * Đăng nhập OTP, trả token Bearer.
     *
     * @param  array<string, mixed>  $extra
     * @param  array<string, string>  $headers
     */
    public static function login(TestCase $test, string $phone = '0912345678', array $extra = [], array $headers = []): string
    {
        $test->postJson(self::API.'/auth/otp/request', ['phone' => $phone], self::CHANNEL)->assertStatus(202);
        $e164 = '+84'.substr($phone, 1);

        return $test->postJson(self::API.'/auth/otp/verify', ['phone' => $phone, 'code' => FakeOtpSender::$codes["{$e164}|login"], ...$extra], [...self::CHANNEL, ...$headers])
            ->assertOk()->json('meta.token');
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
