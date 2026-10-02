<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Settings;

use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;

/**
 * Màn hình cấu hình của cửa hàng: dữ liệu form (giá trị đã đặt + giá trị hiệu lực) và lưu có audit.
 * Secret không bao giờ trả về giao diện.
 */
final class SettingsAdmin
{
    public function __construct(
        private readonly Settings $settings,
        private readonly Extensions $extensions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @return list<string>
     */
    public function namespaces(): array
    {
        $namespaces = array_values(array_unique(array_map(fn (SettingDefinition $definition): string => $definition->namespace, $this->settings->definitions())));
        usort($namespaces, fn (string $a, string $b): int => ($a === 'core' ? -1 : 0) <=> ($b === 'core' ? -1 : 0) ?: strcmp($a, $b));

        return $namespaces;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function form(string $namespace): array
    {
        $explicit = $this->settings->explicit($namespace);

        $fields = [];
        foreach ($this->settings->definitions($namespace) as $definition) {
            $secret = $definition->type === 'secret';
            $fields[] = [
                'key' => $definition->key,
                'label' => $definition->label,
                'type' => $definition->type,
                'help' => $definition->help,
                'options' => $this->options($definition),
                'is_set' => array_key_exists($definition->key, $explicit),
                'value' => $secret ? null : ($explicit[$definition->key] ?? null),
                'effective' => $secret ? null : $this->settings->get($namespace, $definition->key),
            ];
        }

        return $fields;
    }

    /**
     * @param  array<string, mixed>  $values  key => giá trị; null = xoá (về mặc định). Secret rỗng = giữ nguyên.
     */
    public function save(string $namespace, array $values): void
    {
        $definitions = [];
        foreach ($this->settings->definitions($namespace) as $definition) {
            $definitions[$definition->key] = $definition;
        }

        foreach ($values as $key => $value) {
            $definition = $definitions[$key] ?? throw ValidationException::withMessages(["values.{$key}" => 'Cấu hình không tồn tại.']);
            if ($definition->type === 'secret' && ($value === null || $value === '')) {
                continue;
            }

            if ($value === null || $value === '') {
                $this->settings->forget($namespace, $key);
                $this->audit->record('settings.reset', 'setting', "{$namespace}.{$key}");

                continue;
            }

            $this->settings->set($namespace, $key, $this->cast($definition, $value));
            $this->audit->record('settings.updated', 'setting', "{$namespace}.{$key}", [
                'value' => $definition->type === 'secret' ? '***' : $value,
            ]);
        }
    }

    private function cast(SettingDefinition $definition, mixed $value): mixed
    {
        $error = fn (string $message) => ValidationException::withMessages(["values.{$definition->key}" => $message]);

        return match ($definition->type) {
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw $error('Phải là số nguyên.'),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? throw $error('Phải là đúng/sai.'),
            'select' => array_key_exists((string) $value, $this->options($definition)) ? (string) $value : throw $error('Giá trị không nằm trong danh sách.'),
            default => is_scalar($value) && mb_strlen((string) $value) <= 2000 ? (string) $value : throw $error('Tối đa 2000 ký tự.'),
        };
    }

    /**
     * @return array<string, string>
     */
    private function options(SettingDefinition $definition): array
    {
        if ($definition->optionsFromTag === null) {
            return $definition->options;
        }

        $options = $definition->options;
        foreach ($this->extensions->tagged($definition->optionsFromTag) as $implementation) {
            if (method_exists($implementation, 'code')) {
                $code = (string) $implementation->code();
                $options[$code] = method_exists($implementation, 'label') ? (string) $implementation->label() : $code;
            }
        }

        return $options;
    }
}
