<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpSender;
use Modules\Customer\Domain\OtpCode;
use Modules\Extension\Contracts\Extensions;

/**
 * OTP qua kênh `OtpSender`. Chống SMS pumping/brute-force: giới hạn yêu cầu theo SĐT và IP, tối đa 5 lần
 * nhập sai cho mỗi mã, mã mới vô hiệu mã cũ.
 */
final class OtpService
{
    public function __construct(
        private readonly Extensions $extensions,
        private readonly CustomerService $customers,
        private readonly string $secret,
        private readonly int $perPhone = 3,
        private readonly int $perIp = 10,
        private readonly int $windowSeconds = 600,
    ) {}

    /**
     * @return array{channel: string, expires_in: int}
     */
    public function request(string $e164, OtpPurpose $purpose, string $ip): array
    {
        foreach (["customer-otp:phone:{$e164}" => $this->perPhone, "customer-otp:ip:{$ip}" => $this->perIp] as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw CustomerRejected::otpRateLimited(RateLimiter::availableIn($key));
            }
        }

        $customer = $this->customers->activeByPhone($e164);
        $contact = new CustomerContact($e164, $customer?->email, $customer?->full_name, $customer?->id);
        $sender = $this->sender($contact) ?? throw CustomerRejected::otpUnavailable();

        foreach (["customer-otp:phone:{$e164}", "customer-otp:ip:{$ip}"] as $key) {
            RateLimiter::hit($key, $this->windowSeconds);
        }

        $code = OtpCode::generate();
        DB::transaction(function () use ($e164, $purpose, $code, $sender, $ip): void {
            DB::table('customer_otps')->where('phone', $e164)->where('purpose', $purpose->value)->whereNull('consumed_at')->update(['consumed_at' => now()]);
            DB::table('customer_otps')->insert([
                'phone' => $e164, 'purpose' => $purpose->value, 'code_hash' => OtpCode::hash($this->secret, $e164, $code), 'channel' => $sender->channel(),
                'expires_at' => now()->addSeconds(OtpCode::TTL_SECONDS), 'ip' => $ip, 'created_at' => now(),
            ]);
        });

        $sender->send($contact, $code, $purpose);

        return ['channel' => $sender->channel(), 'expires_in' => OtpCode::TTL_SECONDS];
    }

    /**
     * Dùng mã (một lần). Sai → tăng số lần thử; quá 5 lần mã bị huỷ.
     *
     * @throws CustomerRejected otp_invalid
     */
    public function verify(string $e164, OtpPurpose $purpose, string $code): void
    {
        $valid = OtpCode::isWellFormed($code) && DB::transaction(function () use ($e164, $purpose, $code): bool {
            $otp = DB::table('customer_otps')->where('phone', $e164)->where('purpose', $purpose->value)
                ->whereNull('consumed_at')->where('expires_at', '>', now())
                ->orderByDesc('id')->lockForUpdate()->first();
            if ($otp === null) {
                return false;
            }

            if (! hash_equals((string) $otp->code_hash, OtpCode::hash($this->secret, $e164, $code))) {
                $attempts = (int) $otp->attempts + 1;
                DB::table('customer_otps')->where('id', $otp->id)->update([
                    'attempts' => $attempts, 'consumed_at' => $attempts >= OtpCode::MAX_ATTEMPTS ? now() : null,
                ]);

                return false;
            }

            DB::table('customer_otps')->where('id', $otp->id)->update(['consumed_at' => now()]);

            return true;
        });

        if (! $valid) {
            throw CustomerRejected::otpInvalid();
        }
    }

    private function sender(CustomerContact $contact): ?OtpSender
    {
        $senders = array_filter($this->extensions->tagged(OtpSender::TAG), fn (object $sender): bool => $sender instanceof OtpSender);
        usort($senders, fn (OtpSender $a, OtpSender $b): int => $b->priority() <=> $a->priority());

        foreach ($senders as $sender) {
            if ($sender->isAvailable($contact)) {
                return $sender;
            }
        }

        return null;
    }
}
