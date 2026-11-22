<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/** Middleware phiên của framework cần bỏ khi route tự quyết định cách mở phiên. */
final class SessionlessRoutes
{
    public const MIDDLEWARE = [StartSession::class, ShareErrorsFromSession::class, PreventRequestForgery::class, ValidateCsrfToken::class];
}
