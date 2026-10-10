<?php

declare(strict_types=1);

namespace Modules\Integration\Domain;

/**
 * Chữ ký `X-Vani-Signature: t=<unix>,v1=<hex(hmac_sha256(secret, t + "." + payload))>`.
 *
 * - Webhook gửi đi: payload = body.
 * - Request Integration API: payload = METHOD + "." + path?query + "." + body, để chữ ký gắn với đúng
 *   endpoint (GET không có body vẫn không dùng lại được cho đường dẫn khác).
 *
 * @see docs/06-api/api.md §5.1
 */
final class HmacSignature
{
    public const TOLERANCE_SECONDS = 300;

    public static function header(string $secret, string $payload, int $timestamp): string
    {
        return 't='.$timestamp.',v1='.self::compute($secret, $payload, $timestamp);
    }

    public static function compute(string $secret, string $payload, int $timestamp): string
    {
        return hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
    }

    public static function requestPayload(string $method, string $pathWithQuery, string $body): string
    {
        return strtoupper($method).'.'.$pathWithQuery.'.'.$body;
    }

    /**
     * @return array{timestamp: int, signatures: list<string>}|null
     */
    public static function parse(string $header): ?array
    {
        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $header) as $part) {
            [$name, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($name === 't' && ctype_digit($value)) {
                $timestamp = (int) $value;
            } elseif ($name === 'v1' && $value !== '') {
                $signatures[] = $value;
            }
        }

        return $timestamp === null || $signatures === [] ? null : ['timestamp' => $timestamp, 'signatures' => $signatures];
    }

    public static function verify(string $secret, string $payload, string $header, int $now, int $toleranceSeconds = self::TOLERANCE_SECONDS): bool
    {
        $parsed = self::parse($header);
        if ($parsed === null || abs($now - $parsed['timestamp']) > $toleranceSeconds) {
            return false;
        }

        $expected = self::compute($secret, $payload, $parsed['timestamp']);
        foreach ($parsed['signatures'] as $signature) {
            if (hash_equals($expected, $signature)) {
                return true;
            }
        }

        return false;
    }
}
