<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use Modules\Shared\Contracts\PluginApi;

/**
 * Trường form do plugin khai báo (phần form trên màn hình Admin của Core — ADR-030). Admin render chung,
 * Core validate theo kiểu trước khi gọi `save` của plugin.
 */
#[PluginApi]
final readonly class FieldDefinition
{
    /** `media` (0.3.87): id ảnh của Thư viện ảnh; `video` (0.3.91): id video của Thư viện ảnh — ô chọn dùng chung. */
    public const TYPES = ['string', 'text', 'int', 'bool', 'select', 'date', 'media', 'video', 'repeater'];

    /**
     * @param  array<array-key, string>  $options  (select) giá trị => nhãn
     * @param  list<self>  $fields  các trường trong mỗi dòng repeater của Builder
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'string',
        public bool $required = false,
        public array $options = [],
        public ?string $help = null,
        public int $max = 255,
        public array $fields = [],
        public bool $editable = true,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Kiểu trường [{$type}] không hỗ trợ.");
        }
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key) !== 1) {
            throw new \InvalidArgumentException("Khoá trường [{$key}] phải là snake_case.");
        }
        if ($type === 'repeater' && ($max < 1 || $max > 50 || $fields === [] || count(array_unique(array_column($fields, 'key'))) !== count($fields) || in_array('repeater', array_column($fields, 'type'), true))) {
            throw new \InvalidArgumentException('Repeater cần từ 1 đến 50 dòng và các trường con có khoá riêng, một cấp.');
        }
    }

    public static function string(string $key, string $label, bool $required = false, ?string $help = null, int $max = 255): self
    {
        return new self($key, $label, 'string', $required, help: $help, max: $max);
    }

    public static function text(string $key, string $label, bool $required = false, ?string $help = null, int $max = 5000): self
    {
        return new self($key, $label, 'text', $required, help: $help, max: $max);
    }

    public static function int(string $key, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'int', $required, help: $help);
    }

    public static function bool(string $key, string $label, ?string $help = null): self
    {
        return new self($key, $label, 'bool', help: $help);
    }

    /**
     * @param  array<array-key, string>  $options
     */
    public static function select(string $key, string $label, array $options, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'select', $required, $options, $help);
    }

    public static function date(string $key, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'date', $required, help: $help);
    }

    /**
     * Ảnh của Thư viện ảnh (0.3.87): giá trị là id ảnh; hiển thị bằng `vani_image($id, …)` / `vani_srcset($id)`.
     */
    public static function media(string $key, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'media', $required, help: $help);
    }

    /**
     * Video của Thư viện ảnh (0.3.91, mp4/webm): giá trị là id; hiển thị bằng `vani_media($id)` (URL file gốc).
     */
    public static function video(string $key, string $label, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'video', $required, help: $help);
    }

    /** @param list<self> $fields */
    public static function repeater(string $key, string $label, array $fields, int $max = 12, bool $required = false, ?string $help = null): self
    {
        return new self($key, $label, 'repeater', $required, help: $help, max: $max, fields: $fields);
    }

    /**
     * @return list<mixed>
     */
    public function rules(): array
    {
        $presence = $this->required ? 'required' : 'nullable';

        return match ($this->type) {
            'string', 'text' => [$presence, 'string', "max:{$this->max}"],
            'int' => [$presence, 'integer'],
            'bool' => ['nullable', 'boolean'],
            'select' => [$presence, 'string', 'in:'.implode(',', array_map('strval', array_keys($this->options)))],
            'date' => [$presence, 'date_format:Y-m-d'],
            'media', 'video' => [$presence, 'integer', 'min:1'],
            'repeater' => [$presence, 'array', 'list', "max:{$this->max}"],
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        // Nhãn/help/lựa chọn có thể là khoá dịch (khai báo lúc boot) — dịch theo ngôn ngữ hiện tại (ADR-049).
        return [
            'key' => $this->key, 'label' => __($this->label), 'type' => $this->type, 'required' => $this->required,
            'options' => array_map(fn (string $label): string => (string) __($label), $this->options),
            'help' => $this->help === null ? null : __($this->help),
        ] + ($this->type === 'repeater' ? [
            'max' => $this->max,
            'fields' => array_values(array_map(fn (self $field): array => $field->toArray(), array_filter($this->fields, fn (self $field): bool => $field->editable))),
        ] : []);
    }
}
