<?php

declare(strict_types=1);

namespace Modules\Customer\Tests\Feature\Fixtures;

use Modules\Customer\Contracts\AuthProvider;
use Modules\Customer\Contracts\AuthProviderFailed;
use Modules\Customer\Contracts\Data\ExternalIdentity;

/**
 * Nhà cung cấp đăng nhập giả: `code` quyết định danh tính trả về.
 */
final class FakeAuthProvider implements AuthProvider
{
    public function code(): string
    {
        return 'fake_id';
    }

    public function label(): string
    {
        return 'Fake ID';
    }

    public function authorizationUrl(string $state, string $redirectUri): string
    {
        return 'https://id.example/authorize?'.http_build_query(['state' => $state, 'redirect_uri' => $redirectUri]);
    }

    public function resolve(array $params, string $redirectUri): ExternalIdentity
    {
        return match ($params['code'] ?? '') {
            'phone' => new ExternalIdentity('fake_id', 'user-phone', '0912345678', phoneVerified: true, fullName: 'Lan'),
            'phone-unverified' => new ExternalIdentity('fake_id', 'user-unverified', '0912345678', phoneVerified: false),
            'email' => new ExternalIdentity('fake_id', 'user-email', email: 'Lan@Example.com', emailVerified: true),
            'email-unverified' => new ExternalIdentity('fake_id', 'user-email-2', email: 'lan@example.com', emailVerified: false),
            default => throw new AuthProviderFailed('Mã không hợp lệ.'),
        };
    }
}
