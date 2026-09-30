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
 * Chạy SAU `vani.api-channel`: giữ kênh/brand/locale, chỉ thay actor bằng khách hàng.
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
        if ($token === '') {
            if ($mode === 'optional') {
                return $next($request);
            }
            throw CustomerRejected::unauthenticated();
        }

        $customer = $this->auth->authenticate($token) ?? throw CustomerRejected::unauthenticated();
        $request->attributes->set(self::ATTRIBUTE, $customer);

        $scope = $this->context->has() ? $this->context->scope() : null;
        $this->context->set(new ContextScope(
            new Actor(ActorType::Customer, $customer->id, $customer->public_id),
            $scope?->channelId,
            $scope === null ? [] : $scope->brandIds,
            $scope?->locale,
        ));

        return $next($request);
    }

    public static function customer(Request $request): Customer
    {
        $customer = $request->attributes->get(self::ATTRIBUTE);

        return $customer instanceof Customer ? $customer : throw CustomerRejected::unauthenticated();
    }
}
