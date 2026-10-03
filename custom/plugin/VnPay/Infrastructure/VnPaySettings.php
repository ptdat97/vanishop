<?php

declare(strict_types=1);

namespace Plugin\VnPay\Infrastructure;

use Modules\Tenancy\Contracts\Settings;
use Plugin\VnPay\VnPayServiceProvider;

/**
 * Cấu hình đang hiệu lực: Admin → Cấu hình (vani.vnpay) trước, rồi config/env.
 */
final class VnPaySettings
{
    private const PAY_URL = ['sandbox' => 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html', 'live' => 'https://pay.vnpay.vn/vpcpay.html'];

    private const API_URL = ['sandbox' => 'https://sandbox.vnpayment.vn/merchant_webapi/api/transaction', 'live' => 'https://merchant.vnpay.vn/merchant_webapi/api/transaction'];

    public function __construct(private readonly Settings $settings) {}

    public function tmnCode(): string
    {
        return trim((string) $this->get('tmn_code'));
    }

    public function hashSecret(): string
    {
        return trim((string) $this->get('hash_secret'));
    }

    public function configured(): bool
    {
        return $this->tmnCode() !== '' && $this->hashSecret() !== '';
    }

    public function ttl(): int
    {
        return max(300, (int) $this->get('ttl'));
    }

    public function payUrl(): string
    {
        return self::PAY_URL[$this->environment()];
    }

    public function apiUrl(): string
    {
        return self::API_URL[$this->environment()];
    }

    public function returnUrl(): string
    {
        $configured = trim((string) config('vani.vnpay.return_url', ''));

        return $configured !== '' ? $configured : route('storefront.p.vani-vnpay.return');
    }

    public function signer(): VnPaySigner
    {
        return new VnPaySigner($this->hashSecret());
    }

    private function environment(): string
    {
        return filter_var($this->get('sandbox'), FILTER_VALIDATE_BOOL) ? 'sandbox' : 'live';
    }

    private function get(string $key): mixed
    {
        return $this->settings->get(VnPayServiceProvider::ID, $key, config("vani.vnpay.{$key}"));
    }
}
