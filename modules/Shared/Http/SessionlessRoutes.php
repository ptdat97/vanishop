<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * Middleware bỏ khỏi route trang công khai cache được (`vani.page-cache`, storefront §5): không phiên, không CSRF, không
 * nhận diện khách — HTML giống nhau với mọi người, không Set-Cookie.
 */
final class SessionlessRoutes
{
    public const MIDDLEWARE = [StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, ValidateCsrfToken::class, 'vani.customer-session'];
}
