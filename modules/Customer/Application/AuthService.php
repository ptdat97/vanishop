<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Customer\Contracts\Data\ExternalIdentity;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Events\CustomerRegistered;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Customer\Persistence\Models\CustomerIdentity;
use Modules\Customer\Persistence\Models\CustomerToken;
use Modules\Shared\Support\Phones;

/**
 * Đăng nhập khách: OTP (mặc định) hoặc mật khẩu (tuỳ chọn, đặt sau khi đã xác thực SĐT). Phiên API là token
 * Bearer mờ, chỉ lưu sha256 (ADR-024).
 */
final class AuthService
{
    public function __construct(
        private readonly OtpService $otp,
        private readonly CustomerService $customers,
        private readonly int $tokenTtlDays = 90,
    ) {}

    /**
     * Xác thực OTP → đăng nhập. SĐT chưa có hồ sơ → tạo; profile ẩn (đã đặt hàng vãng lai) → thành tài khoản,
     * giữ nguyên lịch sử đơn.
     *
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function loginWithOtp(string $e164, string $code, ?string $device = null): array
    {
        $this->otp->verify($e164, OtpPurpose::Login, $code);

        return DB::transaction(function () use ($e164, $device): array {
            $guestProfile = $this->customers->activeByPhone($e164);
            $customer = $guestProfile ?? $this->customers->findOrCreateByPhone($e164);
            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            if (! $customer->isRegistered()) {
                $customer->update(['registered_at' => now(), 'phone_verified_at' => now()]);
                event(new CustomerRegistered($customer->id, $customer->public_id, claimedGuestProfile: $guestProfile !== null));
            }

            return $this->startSession($customer, $device);
        });
    }

    /**
     * Đăng nhập bằng danh tính bên ngoài (AuthProvider). Ghép theo thứ tự: danh tính đã liên kết → SĐT đã xác minh
     * (tạo khách nếu chưa có) → email đã xác minh của khách đang có. Không có → `customer.social_phone_required`.
     *
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function loginWithIdentity(ExternalIdentity $identity, ?string $device = null): array
    {
        return DB::transaction(function () use ($identity, $device): array {
            $linked = CustomerIdentity::query()->where('provider', $identity->provider)->where('subject', $identity->subject)->first();
            $customer = $linked === null ? null : Customer::query()->whereKey($linked->customer_id)->where('status', CustomerStatus::Active)->first();

            if ($customer === null) {
                $phone = $identity->phoneVerified ? Phones::parse((string) $identity->phone) : null;
                $customer = match (true) {
                    $phone !== null => $this->customers->findOrCreateByPhone($phone->e164, $identity->fullName),
                    $identity->emailVerified && $identity->email !== null => Customer::query()->where('status', CustomerStatus::Active)
                        ->whereRaw('LOWER(email) = ?', [mb_strtolower($identity->email)])->first(),
                    default => null,
                } ?? throw CustomerRejected::socialPhoneRequired();

                CustomerIdentity::query()->updateOrCreate(
                    ['provider' => $identity->provider, 'subject' => $identity->subject],
                    ['customer_id' => $customer->id],
                );
            }

            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if (! $customer->isRegistered()) {
                $customer->update(['registered_at' => now()] + ($customer->phone !== null && $identity->phoneVerified ? ['phone_verified_at' => now()] : []));
                event(new CustomerRegistered($customer->id, $customer->public_id, claimedGuestProfile: $customer->orders_count > 0));
            }
            CustomerIdentity::query()->where('provider', $identity->provider)->where('subject', $identity->subject)->update(['last_used_at' => now()]);

            return $this->startSession($customer, $device);
        });
    }

    /**
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function loginWithPassword(string $e164, string $password, string $ip, ?string $device = null): array
    {
        $key = "customer-login:{$e164}|{$ip}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw CustomerRejected::otpRateLimited(RateLimiter::availableIn($key));
        }

        $customer = $this->customers->activeByPhone($e164);
        if ($customer === null || ! $customer->isRegistered() || $customer->password === null || ! Hash::check($password, $customer->password)) {
            RateLimiter::hit($key, 60);
            throw CustomerRejected::invalidCredentials();
        }

        RateLimiter::clear($key);

        return $this->startSession($customer, $device);
    }

    public function authenticate(string $token): ?Customer
    {
        if ($token === '') {
            return null;
        }

        $record = CustomerToken::query()->with('customer')->where('token_hash', hash('sha256', $token))->first();
        if ($record === null || $record->expires_at->isPast() || ! $record->customer->isActive()) {
            return null;
        }

        if ($record->last_used_at === null || $record->last_used_at->lt(now()->subMinutes(5))) {
            $record->forceFill(['last_used_at' => now()])->save();
        }

        return $record->customer;
    }

    public function logout(string $token): void
    {
        CustomerToken::query()->where('token_hash', hash('sha256', $token))->delete();
    }

    public function revokeAll(int $customerId): void
    {
        CustomerToken::query()->where('customer_id', $customerId)->delete();
    }

    public function setPassword(int $customerId, ?string $current, string $new): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        if ($customer->password !== null && ($current === null || ! Hash::check($current, $customer->password))) {
            throw CustomerRejected::passwordMismatch();
        }

        $customer->update(['password' => $new]);
    }

    /**
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    private function startSession(Customer $customer, ?string $device): array
    {
        $token = Str::random(64);
        CustomerToken::query()->create([
            'customer_id' => $customer->id, 'token_hash' => hash('sha256', $token), 'name' => $device === null ? null : mb_substr($device, 0, 64),
            'expires_at' => now()->addDays($this->tokenTtlDays),
        ]);
        $customer->update(['last_login_at' => now()]);

        return ['customer' => CustomerService::toData($customer), 'token' => $token, 'customer_id' => $customer->id];
    }
}
