<?php

declare(strict_types=1);

namespace Modules\Customer\Testing;

use Closure;
use Modules\Customer\Contracts\Data\CustomerContact;
use Modules\Customer\Contracts\Data\OtpPurpose;
use Modules\Customer\Contracts\OtpDeliveryFailed;
use Modules\Customer\Contracts\OtpSender;
use Throwable;

/**
 * Contract test cho OtpSender. Kiểm tra: mã kênh ổn định; priority là số nguyên; gửi thành công không ném; nhà cung
 * cấp lỗi → CHỈ ném OtpDeliveryFailed (Core dựa vào đó để chuyển kênh), không ném exception khác.
 */
final class OtpSenderContract
{
    /**
     * @param  Closure(): OtpSender  $sender
     * @param  Closure(): void  $succeed
     * @param  Closure(): void  $fail
     */
    public static function define(string $label, Closure $sender, Closure $succeed, Closure $fail, ?CustomerContact $contact = null): void
    {
        $contact ??= new CustomerContact('+84912345678', 'lan@example.com', 'Nguyễn Thị Lan');

        describe("OtpSender contract: {$label}", function () use ($sender, $succeed, $fail, $contact): void {
            it('có mã kênh ổn định và khả dụng với liên hệ mẫu', function () use ($sender, $contact): void {
                expect($sender()->channel())->toMatch('/^[a-z][a-z0-9_]*$/')
                    ->and($sender()->priority())->toBeInt()
                    ->and($sender()->isAvailable($contact))->toBeTrue();
            });

            it('gửi thành công không ném exception', function () use ($sender, $succeed, $contact): void {
                $succeed();
                $sender()->send($contact, '123456', OtpPurpose::Login);
                expect(true)->toBeTrue();
            });

            it('nhà cung cấp lỗi → chỉ ném OtpDeliveryFailed', function () use ($sender, $fail, $contact): void {
                $fail();
                $thrown = null;
                try {
                    $sender()->send($contact, '123456', OtpPurpose::Login);
                } catch (Throwable $exception) {
                    $thrown = $exception;
                }
                expect($thrown)->toBeInstanceOf(OtpDeliveryFailed::class);
            });
        });
    }
}
