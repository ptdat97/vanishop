<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
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
use Modules\Shared\Support\StoreLocale;

/**
 * Đăng nhập khách. Mặc định của Core (0.5.12): **email + mật khẩu** — đăng ký chờ xác minh qua link email, tài khoản chỉ tạo
 * (hoặc nhận hồ sơ khách vãng lai cùng email) khi bấm link, nên không ai chiếm được lịch sử đơn của email không thuộc mình.
 * OTP theo SĐT là phương thức bổ sung do plugin bật (`vani.phone-otp-login` qua contract `PhoneOtpLogin`); đăng nhập mạng xã
 * hội qua `AuthProvider`. Phiên API là token Bearer mờ, chỉ lưu sha256 (ADR-024).
 */
final class AuthService
{
    private const REGISTRATION_KEY = 'vani:customer-registration:';

    private const RESET_KEY = 'vani:customer-password-reset:';

    private const EMAIL_VERIFY_KEY = 'vani:customer-email-verify:';

    /** Số email đăng ký/đặt lại gửi tới một địa chỉ trong 10 phút (chống dùng cửa hàng để spam hộp thư). */
    private const EMAILS_PER_ADDRESS = 3;

    public function __construct(
        private readonly OtpService $otp,
        private readonly CustomerService $customers,
        private readonly AccountEmails $emails,
        private readonly ConsentService $consents,
        private readonly int $tokenTtlDays = 90,
        private readonly int $verifyTtlHours = 24,
        private readonly int $resetTtlMinutes = 60,
    ) {}

    /**
     * Đăng ký bằng email: chưa ghi gì vào DB — giữ yêu cầu (mật khẩu đã băm) trong cache và gửi link xác minh. Email đã là tài
     * khoản → `customer.email_taken`. Gửi quá nhiều link tới một địa chỉ → `customer.too_many_attempts`.
     *
     * @return array{email: string, expires_in: int}
     */
    public function register(string $email, string $password, string $fullName, bool $marketingEmail = false): array
    {
        $email = self::normalizeEmail($email);
        if ($this->activeByEmail($email)?->isRegistered() === true) {
            throw CustomerRejected::emailTaken();
        }
        $this->throttleEmails("customer-register:{$email}");

        $token = Str::random(64);
        Cache::put(self::REGISTRATION_KEY.hash('sha256', $token), [
            'email' => $email, 'password' => Hash::make($password), 'full_name' => trim($fullName), 'locale' => App::getLocale(), 'marketing_email' => $marketingEmail,
        ], now()->addHours($this->verifyTtlHours));
        $this->emails->sendVerification($email, trim($fullName), $token, $this->verifyTtlHours);

        return ['email' => $email, 'expires_in' => $this->verifyTtlHours * 3600];
    }

    /**
     * Bấm link xác minh → tạo tài khoản và đăng nhập. Hồ sơ khách vãng lai cùng email (đã đặt hàng) thành tài khoản, giữ lịch
     * sử đơn. Link dùng một lần; sai/hết hạn → `customer.registration_link_invalid`.
     *
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function verifyRegistration(string $token, ?string $device = null): array
    {
        $pending = Cache::pull(self::REGISTRATION_KEY.hash('sha256', $token));
        if (! is_array($pending) || ! isset($pending['email'], $pending['password'])) {
            throw CustomerRejected::registrationLinkInvalid();
        }

        try {
            return DB::transaction(function () use ($pending, $device): array {
                $guestProfile = Customer::query()->where('email_normalized', $pending['email'])->lockForUpdate()->first();
                if ($guestProfile?->isRegistered() === true) {
                    throw CustomerRejected::emailTaken();
                }
                $locale = StoreLocale::isSupported((string) ($pending['locale'] ?? '')) ? (string) $pending['locale'] : null;
                $attributes = ['password' => $pending['password'], 'registered_at' => now(), 'email_verified_at' => now()];

                if ($guestProfile !== null) {
                    $guestProfile->update($attributes + (($guestProfile->full_name ?? '') === '' && $pending['full_name'] !== '' ? ['full_name' => $pending['full_name']] : []));
                    $customer = $guestProfile;
                } else {
                    $customer = Customer::query()->create($attributes + [
                        'public_id' => (string) Str::ulid(), 'email' => $pending['email'], 'status' => CustomerStatus::Active,
                        'full_name' => $pending['full_name'] === '' ? null : $pending['full_name'], 'preferred_locale' => $locale,
                    ]);
                }
                if (($pending['marketing_email'] ?? false) === true) {
                    $this->consents->set($customer->id, 'email', 'marketing', true, 'storefront:register');
                }
                event(new CustomerRegistered($customer->id, $customer->public_id, claimedGuestProfile: $guestProfile !== null));

                return $this->startSession($customer, $device);
            });
        } catch (UniqueConstraintViolationException) {
            throw CustomerRejected::emailTaken();
        }
    }

    /**
     * Xác thực OTP (plugin `vani.phone-otp-login` qua `PhoneOtpLogin`) → đăng nhập. SĐT chưa có hồ sơ → tạo; profile ẩn (đã đặt hàng vãng lai) → thành tài khoản,
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
     * Đăng nhập bằng danh tính bên ngoài (AuthProvider). Ghép theo thứ tự: danh tính đã liên kết → email đã xác minh (khách
     * đang có, hồ sơ vãng lai, hoặc tạo tài khoản mới) → SĐT đã xác minh của khách đang có. Không có →
     * `customer.social_email_required`.
     *
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function loginWithIdentity(ExternalIdentity $identity, ?string $device = null): array
    {
        return DB::transaction(function () use ($identity, $device): array {
            $linked = CustomerIdentity::query()->where('provider', $identity->provider)->where('subject', $identity->subject)->first();
            $customer = $linked === null ? null : Customer::query()->whereKey($linked->customer_id)->where('status', CustomerStatus::Active)->first();
            $emailVerified = $identity->emailVerified && $identity->email !== null && filter_var($identity->email, FILTER_VALIDATE_EMAIL) !== false;

            if ($customer === null) {
                $phone = $identity->phoneVerified ? Phones::parse((string) $identity->phone) : null;
                $customer = match (true) {
                    $emailVerified => $this->activeByEmail((string) $identity->email) ?? Customer::query()->create([
                        'public_id' => (string) Str::ulid(), 'email' => self::normalizeEmail((string) $identity->email), 'status' => CustomerStatus::Active,
                        'full_name' => $identity->fullName === null || trim($identity->fullName) === '' ? null : trim($identity->fullName),
                    ]),
                    $phone !== null => $this->customers->activeByPhone($phone->e164),
                    default => null,
                } ?? throw CustomerRejected::socialEmailRequired();

                CustomerIdentity::query()->updateOrCreate(
                    ['provider' => $identity->provider, 'subject' => $identity->subject],
                    ['customer_id' => $customer->id],
                );
            }

            $customer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            if ($emailVerified && $customer->email_verified_at === null && self::normalizeEmail((string) $customer->email) === self::normalizeEmail((string) $identity->email)) {
                $customer->update(['email_verified_at' => now()]);
            }
            if (! $customer->isRegistered()) {
                $customer->update(['registered_at' => now()] + ($customer->phone !== null && $identity->phoneVerified ? ['phone_verified_at' => now()] : []));
                event(new CustomerRegistered($customer->id, $customer->public_id, claimedGuestProfile: $customer->orders_count > 0));
            }
            CustomerIdentity::query()->where('provider', $identity->provider)->where('subject', $identity->subject)->update(['last_used_at' => now()]);

            return $this->startSession($customer, $device);
        });
    }

    /**
     * Đăng nhập bằng email + mật khẩu (tài khoản đã đăng ký). Sai 5 lần/phút theo email + IP → `customer.too_many_attempts`.
     *
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function loginWithPassword(string $email, string $password, string $ip, ?string $device = null): array
    {
        $email = self::normalizeEmail($email);
        $key = "customer-login:{$email}|{$ip}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw CustomerRejected::tooManyAttempts(RateLimiter::availableIn($key));
        }

        $customer = $this->activeByEmail($email);
        if ($customer === null || ! $customer->isRegistered() || $customer->password === null || ! Hash::check($password, $customer->password)) {
            RateLimiter::hit($key, 60);
            throw CustomerRejected::invalidCredentials();
        }

        RateLimiter::clear($key);

        return $this->startSession($customer, $device);
    }

    /**
     * Quên mật khẩu: gửi link đặt lại nếu email là tài khoản đã đăng ký. Không báo email có tồn tại hay không (chống dò
     * tài khoản) — kể cả khi bị giới hạn tần suất.
     */
    public function requestPasswordReset(string $email): void
    {
        $email = self::normalizeEmail($email);
        $customer = $this->activeByEmail($email);
        if ($customer === null || ! $customer->isRegistered() || RateLimiter::tooManyAttempts("customer-reset:{$email}", self::EMAILS_PER_ADDRESS)) {
            return;
        }
        RateLimiter::hit("customer-reset:{$email}", 600);

        $token = Str::random(64);
        Cache::put(self::RESET_KEY.hash('sha256', $token), ['customer_id' => $customer->id, 'email' => $email], now()->addMinutes($this->resetTtlMinutes));
        $this->emails->sendPasswordReset($email, $customer->full_name, $token, $this->resetTtlMinutes);
    }

    /**
     * Đặt mật khẩu mới bằng link email (dùng một lần): thu hồi mọi phiên cũ; link chứng minh sở hữu email nên email được
     * đánh dấu đã xác minh.
     */
    public function resetPassword(string $token, string $password): void
    {
        $pending = $this->consumeEmailChallenge(self::RESET_KEY, $token);
        DB::transaction(function () use ($pending, $password): void {
            $customer = $this->emailChallengeCustomer($pending);
            if ($customer === null) {
                throw CustomerRejected::passwordResetInvalid();
            }

            $customer->update(['password' => $password, 'email_verified_at' => $customer->email_verified_at ?? now()]);
            $this->revokeAll($customer->id);
        });
    }

    /**
     * Nhập lại mật khẩu cho thao tác nhạy cảm (xoá tài khoản). Chưa có mật khẩu → `customer.password_required`.
     */
    public function confirmPassword(int $customerId, string $password): void
    {
        $customer = Customer::query()->findOrFail($customerId);
        if ($customer->password === null) {
            throw CustomerRejected::passwordRequired();
        }
        $key = "customer-confirm:{$customerId}";
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw CustomerRejected::tooManyAttempts(RateLimiter::availableIn($key));
        }
        if (! Hash::check($password, $customer->password)) {
            RateLimiter::hit($key, 60);
            throw CustomerRejected::passwordMismatch();
        }
        RateLimiter::clear($key);
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

    public static function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    public function sendEmailVerification(int $customerId): bool
    {
        $customer = Customer::query()->find($customerId);
        if ($customer === null || $customer->email === null || $customer->email === '' || $customer->email_verified_at !== null) {
            return false;
        }
        $email = self::normalizeEmail($customer->email);
        $this->throttleEmails("customer-email-verify:{$email}");

        $token = Str::random(64);
        Cache::put(self::EMAIL_VERIFY_KEY.hash('sha256', $token), ['customer_id' => $customer->id, 'email' => $email], now()->addHours($this->verifyTtlHours));
        $this->emails->sendEmailChangeVerification($customer->email, $customer->full_name, $token, $this->verifyTtlHours);

        return true;
    }

    public function verifyEmail(string $token): ?CustomerData
    {
        $pending = $this->consumeEmailChallenge(self::EMAIL_VERIFY_KEY, $token);

        return DB::transaction(function () use ($pending): ?CustomerData {
            $customer = $this->emailChallengeCustomer($pending);
            if ($customer === null) {
                return null;
            }
            if ($customer->email_verified_at === null) {
                $customer->update(['email_verified_at' => now()]);
            }

            return CustomerService::toData($customer->refresh());
        });
    }

    private function consumeEmailChallenge(string $prefix, string $token): mixed
    {
        $key = $prefix.hash('sha256', $token);

        return Cache::lock($key.':consume', 10)->get(fn (): mixed => Cache::pull($key));
    }

    private function emailChallengeCustomer(mixed $pending): ?Customer
    {
        if (! is_array($pending) || ! isset($pending['customer_id'], $pending['email'])) {
            return null;
        }
        $customer = Customer::query()->where('status', CustomerStatus::Active)->lockForUpdate()->find((int) $pending['customer_id']);

        return $customer?->email !== null && self::normalizeEmail($customer->email) === $pending['email'] ? $customer : null;
    }

    private function activeByEmail(string $email): ?Customer
    {
        return Customer::query()->where('email_normalized', self::normalizeEmail($email))->first();
    }

    private function throttleEmails(string $key): void
    {
        if (RateLimiter::tooManyAttempts($key, self::EMAILS_PER_ADDRESS)) {
            throw CustomerRejected::tooManyAttempts(RateLimiter::availableIn($key));
        }
        RateLimiter::hit($key, 600);
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
