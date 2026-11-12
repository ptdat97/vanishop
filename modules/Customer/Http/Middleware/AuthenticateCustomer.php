<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customer\Application\AuthService;
use Modules\Customer\Contracts\CustomerRejected;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * `Authorization: Bearer <token>` (ADR-024). `vani.customer` bắt buộc đăng nhập; `vani.customer:optional` cho
 * route dùng được cả khi chưa đăng nhập (giỏ, checkout). Token sai/hết hạn luôn là 401, kể cả ở chế độ optional.
 * Chạy SAU `vani.storefront-context`: giữ locale, chỉ thay actor bằng khách hàng.
 */
final class AuthenticateCustomer
{
    public const ATTRIBUTE = 'customer';

    public function __construct(
        private readonly AuthService $auth,
        private readonly CurrentContext $context,
    ) {}

    public function handle(Request $request, Closure $next, string $mode = 'required'): Response
    {
        $token = (string) $request->bearerToken();
        $customer = $token === '' ? $this->fromSession($request) : ($this->auth->authenticate($token) ?? throw CustomerRejected::unauthenticated());
        if ($customer === null) {
            if ($mode === 'optional') {
                return $next($request);
            }
            throw CustomerRejected::unauthenticated();
        }
        $request->attributes->set(self::ATTRIBUTE, $customer);

        $scope = $this->context->has() ? $this->context->scope() : null;
        $this->context->set(new ContextScope(
            new Actor(ActorType::Customer, $customer->id, $customer->public_id),
            $scope?->locale,
        ));

        return $next($request);
    }

    /**
     * Phiên web cùng nguồn (FirstPartySession, ADR-044): JS của theme gọi API bằng phiên đăng nhập của trang. Token trong
     * phiên hết hạn/đã đăng xuất → coi như chưa đăng nhập (như CustomerSession của Blade), không 401.
     */
    private function fromSession(Request $request): ?Customer
    {
        $token = $request->hasSession() ? (string) $request->session()->get(CustomerSession::SESSION_KEY, '') : '';

        return $token === '' ? null : $this->auth->authenticate($token);
    }

    public static function customer(Request $request): Customer
    {
        $customer = $request->attributes->get(self::ATTRIBUTE);

        return $customer instanceof Customer ? $customer : throw CustomerRejected::unauthenticated();
    }
}
