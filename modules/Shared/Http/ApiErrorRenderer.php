<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Định dạng lỗi chuẩn cho /api/*: {"error": {"code", "message", "details", "correlation_id"}}.
 *
 * @see docs/06-api/api.md §2
 */
final class ApiErrorRenderer
{
    public function __invoke(Throwable $exception, Request $request): ?JsonResponse
    {
        if (! $request->is('api/*')) {
            return null;
        }

        [$status, $code, $message, $details] = match (true) {
            $exception instanceof ValidationException => [422, 'validation.failed', __('Dữ liệu không hợp lệ.'), $this->validationDetails($exception)],
            $exception instanceof AuthenticationException => [401, 'auth.unauthenticated', $exception->getMessage(), []],
            $exception instanceof HttpExceptionInterface => [
                $exception->getStatusCode(),
                'http.'.$exception->getStatusCode(),
                $exception->getMessage() !== '' ? $exception->getMessage() : (JsonResponse::$statusTexts[$exception->getStatusCode()] ?? 'Error'),
                [],
            ],
            default => null,
        } ?? [500, 'server.error', config('app.debug') ? $exception->getMessage() : 'Đã có lỗi xảy ra.', []];

        return new JsonResponse([
            'error' => array_filter([
                'code' => $code,
                'message' => $message,
                'details' => $details,
                'correlation_id' => Context::get('correlation_id'),
            ], fn (mixed $value): bool => $value !== []),
        ], $status);
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
