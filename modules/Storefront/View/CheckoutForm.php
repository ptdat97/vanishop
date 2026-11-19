<?php

declare(strict_types=1);

namespace Modules\Storefront\View;

use Illuminate\Contracts\Support\MessageBag;
use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Customer\Contracts\Data\CustomerData;

/**
 * Dữ liệu form checkout dựng sẵn cho theme (`$form` của `theme::pages.checkout`), để template chỉ in ra:
 * - `values`: giá trị từng ô — lần gửi trước (`old`) nếu có; chưa gửi lần nào và khách đã đăng nhập thì điền sẵn từ hồ
 *   sơ và địa chỉ mặc định trong sổ địa chỉ.
 * - `saved_addresses`: sổ địa chỉ của khách cho ô "Địa chỉ đã lưu" (JS điền vào form khi chọn).
 * - `errors`: tóm tắt lỗi đầu form (lỗi chung `business` không gắn ô nào + lỗi từng ô kèm id ô để nhảy tới) — trên
 *   điện thoại khách không phải cuộn tìm ô lỗi.
 */
final class CheckoutForm
{
    /** Ô của form → id phần tử trong `theme::pages.checkout` (đích của link trong tóm tắt lỗi). */
    private const FIELD_IDS = [
        'contact.full_name' => 'contact.full_name',
        'contact.phone' => 'contact.phone',
        'contact.email' => 'contact.email',
        'shipping_address.province_code' => 'province_code',
        'shipping_address.ward_code' => 'ward_code',
        'shipping_address.province_name' => 'shipping_address.province_name',
        'shipping_address.ward_name' => 'shipping_address.ward_name',
        'shipping_address.street_line' => 'shipping_address.street_line',
        'shipping_method' => 'checkout-shipping',
        'payment_method' => 'checkout-payment',
        'note' => 'note',
    ];

    public function __construct(
        private readonly CustomerSessions $sessions,
        private readonly Customers $customers,
    ) {}

    /**
     * @param  array<string, mixed>  $old  input của lần gửi trước (`$request->old()`)
     * @param  int|null  $customerId  khách đã đăng nhập (id nội bộ), null = vãng lai
     * @return array{
     *     values: array<string, string>,
     *     saved_addresses: list<array{id: int, label: string, fields: array<string, string>}>,
     *     errors: list<array{message: string, target: string|null}>
     * }
     */
    public function for(array $old, ?int $customerId, ?MessageBag $errors): array
    {
        $customer = $customerId === null ? null : $this->customers->find($customerId);
        $addresses = $customer === null ? [] : $this->sessions->addresses($customer->id);
        $saved = array_map(fn (array $address): array => [
            'id' => (int) $address['id'],
            'label' => $this->addressLabel($address),
            'fields' => $this->addressFields($address),
        ], $addresses);

        return [
            'values' => $old !== [] ? $this->flatten($old) : $this->prefill($customer, $saved[0]['fields'] ?? []),
            'saved_addresses' => $saved,
            'errors' => $this->errors($errors),
        ];
    }

    /**
     * @param  array<string, string>  $address
     * @return array<string, string>
     */
    private function prefill(?CustomerData $customer, array $address): array
    {
        if ($customer === null) {
            return [];
        }

        return array_filter([
            'contact.full_name' => (string) $customer->fullName,
            'contact.phone' => (string) $customer->phone,
            'contact.email' => (string) $customer->email,
            ...$address,
        ], fn (string $value): bool => $value !== '');
    }

    /**
     * @param  array<string, mixed>  $address
     * @return array<string, string>
     */
    private function addressFields(array $address): array
    {
        $fields = ['contact.full_name' => $address['full_name'] ?? null, 'contact.phone' => $address['phone'] ?? null];
        foreach (['province_code', 'province_name', 'ward_code', 'ward_name', 'street_line'] as $key) {
            $fields["shipping_address.{$key}"] = $address[$key] ?? null;
        }

        return array_map(fn (mixed $value): string => (string) $value, array_filter($fields, fn (mixed $value): bool => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $address
     */
    private function addressLabel(array $address): string
    {
        $parts = array_filter([$address['label'] ?? null, $address['full_name'] ?? null, $address['street_line'] ?? null, $address['ward_name'] ?? null, $address['province_name'] ?? null]);

        return implode(' · ', array_map(fn (mixed $part): string => (string) $part, $parts));
    }

    /**
     * @return list<array{message: string, target: string|null}>
     */
    private function errors(?MessageBag $errors): array
    {
        if ($errors === null) {
            return [];
        }
        $summary = [];
        foreach ($errors->getMessages() as $field => $messages) {
            $summary[] = ['message' => (string) ($messages[0] ?? ''), 'target' => self::FIELD_IDS[$field] ?? null];
        }
        // Lỗi chung lên đầu: thường là lý do quan trọng nhất (tổng đã đổi, hết hàng…).
        usort($summary, fn (array $a, array $b): int => ($a['target'] === null ? 0 : 1) <=> ($b['target'] === null ? 0 : 1));

        return $summary;
    }

    /**
     * `['contact' => ['phone' => …]]` → `['contact.phone' => …]` (chỉ giá trị vô hướng).
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    private function flatten(array $input, string $prefix = ''): array
    {
        $flat = [];
        foreach ($input as $key => $value) {
            $name = $prefix === '' ? (string) $key : "{$prefix}.{$key}";
            if (is_array($value)) {
                $flat += $this->flatten($value, $name);
            } elseif (is_scalar($value)) {
                $flat[$name] = (string) $value;
            }
        }

        return $flat;
    }
}
