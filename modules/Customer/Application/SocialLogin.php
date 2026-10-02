<?php

declare(strict_types=1);

namespace Modules\Customer\Application;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Modules\Customer\Contracts\AuthProvider;
use Modules\Customer\Contracts\AuthProviderFailed;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Extension\Contracts\Extensions;

/**
 * Đăng nhập qua AuthProvider của plugin: `state` một lần (10 phút, gắn nhà cung cấp + redirect_uri), redirect_uri theo
 * danh sách cho phép, rồi ghép danh tính → token Bearer (AuthService::loginWithIdentity).
 */
final class SocialLogin
{
    private const STATE_TTL = 600;

    public function __construct(
        private readonly Extensions $extensions,
        private readonly AuthService $auth,
    ) {}

    /**
     * @return list<array{code: string, label: string}>
     */
    public function providers(): array
    {
        return array_values(array_map(fn (AuthProvider $provider): array => ['code' => $provider->code(), 'label' => $provider->label()], $this->all()));
    }

    /**
     * @return array{url: string, state: string}
     */
    public function begin(string $provider, string $redirectUri): array
    {
        $implementation = $this->provider($provider);
        $this->assertRedirectAllowed($redirectUri);

        $state = Str::random(40);
        Cache::put("vani:social-state:{$state}", ['provider' => $provider, 'redirect_uri' => $redirectUri], self::STATE_TTL);

        return ['url' => $implementation->authorizationUrl($state, $redirectUri), 'state' => $state];
    }

    /**
     * @param  array<string, string>  $params
     * @return array{customer: CustomerData, token: string, customer_id: int}
     */
    public function complete(string $provider, string $state, array $params, ?string $device = null): array
    {
        $implementation = $this->provider($provider);
        $stored = Cache::pull("vani:social-state:{$state}");
        if (! is_array($stored) || $stored['provider'] !== $provider) {
            throw CustomerRejected::socialState();
        }

        try {
            $identity = $implementation->resolve($params, (string) $stored['redirect_uri']);
        } catch (AuthProviderFailed) {
            throw CustomerRejected::socialFailed();
        }
        if ($identity->provider !== $provider || trim($identity->subject) === '') {
            throw CustomerRejected::socialFailed();
        }

        return $this->auth->loginWithIdentity($identity, $device);
    }

    private function provider(string $code): AuthProvider
    {
        return $this->all()[$code] ?? throw CustomerRejected::socialUnknown();
    }

    /**
     * @return array<string, AuthProvider>
     */
    private function all(): array
    {
        return $this->extensions->implementations(AuthProvider::TAG, AuthProvider::class);
    }

    private function assertRedirectAllowed(string $redirectUri): void
    {
        foreach ((array) config('vanishop.customer.auth_redirect_uris', []) as $prefix) {
            if ($prefix !== '' && str_starts_with($redirectUri, rtrim((string) $prefix, '/').'/')) {
                return;
            }
        }

        throw CustomerRejected::socialRedirect();
    }
}
