<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Trường form do plugin khai báo (phần form trên màn hình Admin của Core — ADR-030). Admin render chung,
 * Core validate theo kiểu trước khi gọi `save` của plugin.
 */
final readonly class FieldDefinition
{
    public const TYPES = ['string', 'text', 'int', 'bool', 'select', 'date'];

    /**
     * @param  array<string, string>  $options  (select) giá trị => nhãn
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $type = 'string',
        public bool $required = false,
        public array $options = [],
        public ?string $help = null,
        public int $max = 255,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new \InvalidArgumentException("Kiểu trường [{$type}] không hỗ trợ.");
        }
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key) !== 1) {
            throw new \InvalidArgumentException("Khoá trường [{$key}] phải là snake_case.");
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
     * @param  array<string, string>  $options
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
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'type' => $this->type, 'required' => $this->required, 'options' => $this->options, 'help' => $this->help];
    }
}
