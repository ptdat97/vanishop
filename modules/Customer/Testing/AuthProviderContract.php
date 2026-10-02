<?php

declare(strict_types=1);

namespace Modules\Customer\Testing;

use Closure;
use Modules\Customer\Contracts\AuthProvider;
use Modules\Customer\Contracts\AuthProviderFailed;
use Modules\Customer\Contracts\Data\ExternalIdentity;

/**
 * Contract test cho AuthProvider: mã ổn định + nhãn; URL mang `state`; tham số hợp lệ → danh tính đúng nhà cung cấp,
 * subject ổn định; tham số sai → AuthProviderFailed (không ném lỗi khác).
 *
 *   AuthProviderContract::define('vani.zalo-login', fn () => app(ZaloAuthProvider::class),
 *       validParams: fn () => ['code' => 'ok'], invalidParams: fn () => ['code' => 'expired']);
 */
final class AuthProviderContract
{
    /**
     * @param  Closure(): AuthProvider  $provider
     * @param  Closure(): array<string, string>  $validParams  (có thể giả lập HTTP trước khi trả)
     * @param  Closure(): array<string, string>  $invalidParams
     */
    public static function define(string $label, Closure $provider, Closure $validParams, Closure $invalidParams): void
    {
        describe("AuthProvider contract: {$label}", function () use ($provider, $validParams, $invalidParams): void {
            it('có mã ổn định và nhãn', fn () => expect($provider()->code())->toMatch('/^[a-z0-9][a-z0-9_.-]*$/')->and($provider()->label())->not->toBe(''));

            it('URL chuyển hướng mang state', function () use ($provider): void {
                $url = $provider()->authorizationUrl('STATE1234567890', 'https://shop.example/auth/callback');

                expect(filter_var($url, FILTER_VALIDATE_URL))->not->toBeFalse()->and($url)->toContain('STATE1234567890');
            });

            it('tham số hợp lệ → danh tính đúng nhà cung cấp, subject ổn định', function () use ($provider, $validParams): void {
                $identity = $provider()->resolve($validParams(), 'https://shop.example/auth/callback');

                expect($identity)->toBeInstanceOf(ExternalIdentity::class)
                    ->and($identity->provider)->toBe($provider()->code())
                    ->and($identity->subject)->not->toBe('')
                    ->and($provider()->resolve($validParams(), 'https://shop.example/auth/callback')->subject)->toBe($identity->subject);
            });

            it('tham số sai → AuthProviderFailed', fn () => expect(fn () => $provider()->resolve($invalidParams(), 'https://shop.example/auth/callback'))->toThrow(AuthProviderFailed::class));
        });
    }
}
