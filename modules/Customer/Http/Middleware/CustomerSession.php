<?php

declare(strict_types=1);

namespace Modules\Customer\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\Customer\Application\AuthService;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Symfony\Component\HttpFoundation\Response;

/**
 * Native storefront: token khách lưu trong phiên web (`vani.customer_token`). Có token hợp lệ → actor là khách
 * (giỏ/checkout gắn khách như Storefront API). `vani.customer-session:required` → chưa đăng nhập thì về trang đăng nhập.
 */
final class CustomerSession
{
    public const SESSION_KEY = 'vani.customer_token';

    public function __construct(
        private readonly AuthService $auth,
        private readonly CurrentContext $context,
    ) {}

    public function handle(Request $request, Closure $next, string $mode = 'optional'): Response
    {
        $token = (string) $request->session()->get(self::SESSION_KEY, '');
        $customer = $token === '' ? null : $this->auth->authenticate($token);

        if ($customer === null) {
            if ($token !== '') {
                $request->session()->forget(self::SESSION_KEY);
            }
            if ($mode === 'required') {
                return redirect()->guest(route('storefront.account.login'));
            }

            return $next($request);
        }

        $request->attributes->set(AuthenticateCustomer::ATTRIBUTE, $customer);
        $scope = $this->context->has() ? $this->context->scope() : null;
        $this->context->set(new ContextScope(new Actor(ActorType::Customer, $customer->id, $customer->public_id), $scope?->locale));

        return $next($request);
    }
}
