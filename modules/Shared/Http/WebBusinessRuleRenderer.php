<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Shared\Domain\BusinessRuleViolation;

/**
 * Lỗi nghiệp vụ trong request web (Admin Inertia, native storefront): quay lại trang trước, hiển thị như lỗi form.
 * Request web gửi JSON (`window.vani` của theme, 0.3.93): JSON cùng hình dạng lỗi validation — `{message, errors: {business},
 * code}` với mã trạng thái của lỗi. Request /api/* do ApiErrorRenderer xử lý.
 */
final class WebBusinessRuleRenderer
{
    public function __invoke(BusinessRuleViolation $exception, Request $request): RedirectResponse|JsonResponse|null
    {
        if ($request->is('api/*')) {
            return null;
        }
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => $exception->getMessage(),
                'errors' => ['business' => [$exception->getMessage()]],
                'code' => $exception->errorCode(),
            ], $exception->status());
        }

        return back()->withInput()->withErrors(['business' => $exception->getMessage()]);
    }
}
