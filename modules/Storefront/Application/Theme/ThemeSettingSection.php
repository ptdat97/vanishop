<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Một nhóm cấu hình do theme khai báo trong theme.json `settings` (đầu trang, chân trang…, reference-parity §3.16-3).
 * Trường dùng lại FieldDefinition; theme thêm định dạng `url`/`email` (lưu như string) và trường dịch theo ngôn ngữ.
 */
final readonly class ThemeSettingSection
{
    public const FORMATS = ['url', 'email'];

    /**
     * @param  list<FieldDefinition>  $fields
     * @param  list<string>  $translatable  khoá trường lưu theo ngôn ngữ ({vi: …, en: …})
     * @param  array<string, 'url'|'email'>  $formats  khoá trường => định dạng
     */
    public function __construct(
        public string $code,
        public string $label,
        public array $fields,
        public array $translatable = [],
        public array $formats = [],
    ) {}

    /**
     * @return list<mixed>
     */
    public function rules(FieldDefinition $field): array
    {
        return match ($this->formats[$field->key] ?? null) {
            'url' => [...$field->rules(), 'url:http,https'],
            'email' => [...$field->rules(), 'email'],
            default => $field->rules(),
        };
    }

    public function isTranslatable(string $key): bool
    {
        return in_array($key, $this->translatable, true);
    }

    /**
     * @return array{code: string, label: string, fields: list<array<string, mixed>>}
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'label' => $this->label,
            'fields' => array_map(fn (FieldDefinition $field): array => [
                ...$field->toArray(),
                'format' => $this->formats[$field->key] ?? null,
                'translatable' => $this->isTranslatable($field->key),
            ], $this->fields),
        ];
    }
}
