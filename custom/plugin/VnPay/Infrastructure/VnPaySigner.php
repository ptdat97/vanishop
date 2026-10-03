<?php

declare(strict_types=1);

namespace Plugin\VnPay\Infrastructure;

/**
 * Chữ ký HMAC-SHA512 theo VNPay v2.1.0.
 *
 * - URL thanh toán, Return URL, IPN: tham số `vnp_*` (trừ vnp_SecureHash, vnp_SecureHashType, giá trị rỗng) sắp xếp theo
 *   key, urlencode key và giá trị, nối bằng `&`.
 * - API giao dịch (querydr, refund) và phản hồi của chúng: các trường theo thứ tự cố định, nối bằng `|`.
 */
final class VnPaySigner
{
    public function __construct(private readonly string $secret) {}

    /**
     * @param  array<string, scalar|null>  $params
     */
    public function query(array $params): string
    {
        $params = array_filter($params, fn ($value, $key): bool => str_starts_with((string) $key, 'vnp_')
            && ! in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true) && $value !== null && $value !== '', ARRAY_FILTER_USE_BOTH);
        ksort($params);

        return implode('&', array_map(fn ($key, $value): string => urlencode((string) $key).'='.urlencode((string) $value), array_keys($params), $params));
    }

    /**
     * @param  array<string, scalar|null>  $params
     */
    public function signQuery(array $params): string
    {
        return hash_hmac('sha512', $this->query($params), $this->secret);
    }

    /**
     * @param  array<string, mixed>  $params  có vnp_SecureHash
     */
    public function verifyQuery(array $params): bool
    {
        $received = strtolower((string) ($params['vnp_SecureHash'] ?? ''));

        return $this->secret !== '' && $received !== '' && hash_equals($this->signQuery($params), $received);
    }

    /**
     * @param  list<scalar|null>  $values  đúng thứ tự VNPay quy định cho lệnh
     */
    public function signPipe(array $values): string
    {
        return hash_hmac('sha512', implode('|', array_map(fn ($value): string => (string) $value, $values)), $this->secret);
    }

    /**
     * @param  array<string, mixed>  $response
     * @param  list<string>  $fields
     */
    public function verifyPipe(array $response, array $fields): bool
    {
        $received = strtolower((string) ($response['vnp_SecureHash'] ?? ''));

        return $this->secret !== '' && $received !== ''
            && hash_equals($this->signPipe(array_map(fn (string $field) => $response[$field] ?? '', $fields)), $received);
    }
}
