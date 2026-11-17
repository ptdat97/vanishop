<?php

declare(strict_types=1);

namespace Modules\Shared\Testing;

use Closure;
use Modules\Shared\Contracts\PhoneNumberPolicy;

/**
 * Contract test cho PhoneNumberPolicy. Kiểm tra: mã; số hợp lệ → E.164 (`+`, 7–15 chữ số, không bắt đầu 0); chuẩn hoá lại
 * dạng E.164 hoặc dạng trong nước giữ nguyên số; chuỗi rác → null. Không gọi mạng.
 *
 * @see PhoneNumberPolicy
 */
final class PhoneNumberPolicyContract
{
    /**
     * @param  Closure(): PhoneNumberPolicy  $policy
     * @param  list<string>  $validInputs  cách nhập hợp lệ của thị trường
     */
    public static function define(string $label, Closure $policy, array $validInputs): void
    {
        describe("PhoneNumberPolicy contract: {$label}", function () use ($policy, $validInputs): void {
            it('có mã ổn định', fn () => expect($policy()->code())->toMatch('/^[a-z][a-z0-9_-]*$/'));

            it('số hợp lệ → E.164; chuẩn hoá lại (E.164, dạng trong nước) giữ số; rác → null', function () use ($policy, $validInputs): void {
                foreach ($validInputs as $input) {
                    $e164 = $policy()->normalize($input);
                    expect($e164)->toMatch('/^\+[1-9]\d{6,14}$/')
                        ->and($policy()->normalize((string) $e164))->toBe($e164)
                        ->and($policy()->normalize($policy()->national((string) $e164)))->toBe($e164);
                }
                expect($policy()->normalize('abc'))->toBeNull()->and($policy()->normalize(''))->toBeNull();
            });
        });
    }
}
