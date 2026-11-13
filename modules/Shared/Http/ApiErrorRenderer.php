<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;
use Modules\Shared\Domain\BusinessRuleViolation;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Định dạng lỗi chuẩn cho /api/*: {"error": {"code", "message", "details", "correlation_id"}}.
 * - `code` ổn định: `validation.failed`, mã nghiệp vụ `<module>.<lý_do>`, `auth.unauthenticated`, lỗi HTTP chung
 *   `http.<status>`, `server.error`.
 * - `message` theo locale của request; lỗi HTTP do framework sinh (không tìm thấy model, không có quyền, quá tần suất, hết
 *   phiên…) dùng câu chung trong `shared::errors` — không lộ tên class hay câu mặc định tiếng Anh. Thông điệp ứng dụng tự
 *   đặt khi `abort(404, '…')` được giữ nguyên.
 * - Giữ header của lỗi HTTP (`Retry-After` khi 429, `Allow` khi 405).
 *
 * @see docs/06-api/api.md §2
 */
final class ApiErrorRenderer
{
    /** Câu mặc định (tiếng Anh) của framework cho lỗi HTTP không đi kèm exception gốc. */
    private const FRAMEWORK_MESSAGES = ['Too Many Attempts.', 'This action is unauthorized.', 'CSRF token mismatch.', 'Unauthenticated.', 'Service Unavailable'];

    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        [$status, $code, $message, $details] = match (true) {
            $exception instanceof ValidationException => [422, 'validation.failed', __('shared::errors.validation'), $this->validationDetails($exception)],
            $exception instanceof BusinessRuleViolation => [$exception->status(), $exception->errorCode(), $exception->getMessage(), $exception->details()],
            $exception instanceof AuthenticationException => [401, 'auth.unauthenticated', __('shared::errors.http.401'), []],
            $exception instanceof HttpExceptionInterface => [$exception->getStatusCode(), 'http.'.$exception->getStatusCode(), $this->httpMessage($exception), []],
            default => null,
        } ?? [500, 'server.error', config('app.debug') ? $exception->getMessage() : __('shared::errors.server'), []];
        $headers = $exception instanceof HttpExceptionInterface ? $exception->getHeaders() : [];

        return new JsonResponse([
            'error' => array_filter([
                'code' => $code,
                'message' => $message,
                'details' => $details,
                'correlation_id' => Context::get('correlation_id'),
            ], fn (mixed $value): bool => $value !== []),
        ], $status, $headers);
    }

    /**
     * Thông điệp ứng dụng tự đặt (`abort(404, 'Không có đơn này.')`) được giữ; lỗi do framework chuyển từ exception khác (model
     * không tìm thấy, chưa được phép…) hoặc câu mặc định tiếng Anh → câu chung theo locale.
     */
    private function httpMessage(HttpExceptionInterface $exception): string
    {
        $status = $exception->getStatusCode();
        $generic = __("shared::errors.http.{$status}");
        $fallback = $generic !== "shared::errors.http.{$status}" ? $generic : (JsonResponse::$statusTexts[$status] ?? 'Error');
        $message = $exception->getMessage();
        $converted = $exception->getPrevious() !== null;

        return $message === '' || $converted || in_array($message, self::FRAMEWORK_MESSAGES, true) ? $fallback : $message;
    }

    /**
     * @return list<array{field: string, messages: list<string>}>
     */
    private function validationDetails(ValidationException $exception): array
    {
        return array_map(
            fn (string $field, array $messages): array => ['field' => $field, 'messages' => array_values($messages)],
            array_keys($exception->errors()),
            array_values($exception->errors()),
        );
    }
}
