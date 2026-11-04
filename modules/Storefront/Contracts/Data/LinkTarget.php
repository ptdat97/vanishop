<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

/**
 * Link có kiểu: lưu (loại, giá trị, nhãn ghi đè theo ngôn ngữ), phân giải lúc render bằng `LinkResolver` — đổi slug
 * hay tên đối tượng thì link tự theo. Ví dụ `{type: "category", value: "12"}`, `{type: "url", value: "https://…"}`.
 */
final readonly class LinkTarget
{
    /**
     * @param  array<string, string>  $label  mã ngôn ngữ => nhãn ghi đè (rỗng = nhãn mặc định của đối tượng)
     */
    public function __construct(
        public string $type,
        public string $value,
        public array $label = [],
    ) {}

    /**
     * Từ dữ liệu đã lưu/nhập; thiếu `type` hoặc `value` → null.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): ?self
    {
        $type = trim((string) ($data['type'] ?? ''));
        $value = trim((string) ($data['value'] ?? ''));
        if ($type === '' || $value === '') {
            return null;
        }
        $label = array_filter(array_map(fn (mixed $text): string => trim((string) $text), (array) ($data['label'] ?? [])), fn (string $text): bool => $text !== '');

        return new self($type, $value, $label);
    }

    /**
     * @return array{type: string, value: string, label: array<string, string>}
     */
    public function toArray(): array
    {
        return ['type' => $this->type, 'value' => $this->value, 'label' => $this->label];
    }

    /** Nhãn ghi đè theo ngôn ngữ, null nếu không có. */
    public function labelFor(string $locale): ?string
    {
        return $this->label[$locale] ?? null;
    }
}
