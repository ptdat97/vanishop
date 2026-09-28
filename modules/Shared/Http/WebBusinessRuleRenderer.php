<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Lỗi nghiệp vụ trong request web (Admin Inertia, native storefront): quay lại trang trước, hiển thị như lỗi form.
 * Request /api/* do ApiErrorRenderer xử lý.
 */
final class WebBusinessRuleRenderer
{
    public function __invoke(BusinessRuleViolation $exception, Request $request): ?RedirectResponse
    {
        if ($request->is('api/*') || $request->expectsJson()) {
            return null;
        }

        return back()->withInput()->withErrors(['business' => $exception->getMessage()]);
    }
}
