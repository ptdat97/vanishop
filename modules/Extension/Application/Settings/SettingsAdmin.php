<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Settings;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\SettingPermissions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingGroup;
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
     * Nhóm của Cài đặt hệ thống (namespace `core`) có ít nhất một cấu hình, theo thứ tự của SettingGroup.
     *
     * @return list<array{group: SettingGroup, labels: list<string>, count: int, customized: int}>
     */
    public function systemGroups(): array
    {
        $explicit = $this->settings->explicit('core');
        $groups = [];
        foreach ($this->settings->definitions('core') as $definition) {
            $group = $definition->group ?? SettingGroup::Other;
            $groups[$group->value] ??= ['group' => $group, 'labels' => [], 'count' => 0, 'customized' => 0];
            $groups[$group->value]['labels'][] = self::text($definition->label, $definition->key);
            $groups[$group->value]['count']++;
            $groups[$group->value]['customized'] += array_key_exists($definition->key, $explicit) ? 1 : 0;
        }

        return array_values(array_filter(array_map(fn (SettingGroup $group): ?array => $groups[$group->value] ?? null, SettingGroup::cases())));
    }

    /**
     * Số cấu hình và số mục đã đặt khác mặc định của một namespace (thẻ plugin).
     *
     * @return array{count: int, customized: int}
     */
    public function summary(string $namespace): array
    {
        $explicit = $this->settings->explicit($namespace);
        $definitions = $this->settings->definitions($namespace);

        return [
            'count' => count($definitions),
            'customized' => count(array_filter($definitions, fn (SettingDefinition $definition): bool => array_key_exists($definition->key, $explicit))),
        ];
    }

    /**
     * Namespace của plugin có cấu hình (không gồm `core`).
     *
     * @return list<string>
     */
    public function pluginNamespaces(): array
    {
        return array_values(array_filter($this->namespaces(), fn (string $namespace): bool => $namespace !== 'core'));
    }

    /**
     * @param  SettingGroup|null  $group  chỉ cấu hình của nhóm này (Cài đặt hệ thống; cấu hình không khai nhóm = `Other`)
     * @return list<array<string, mixed>>
     */
    public function form(string $namespace, ?SettingGroup $group = null): array
    {
        $explicit = $this->settings->explicit($namespace);

        $fields = [];
        foreach ($this->settings->definitions($namespace) as $definition) {
            if ($group !== null && ($definition->group ?? SettingGroup::Other) !== $group) {
                continue;
            }
            $secret = $definition->type === 'secret';
            $fields[] = [
                'key' => $definition->key,
                'label' => self::text($definition->label, $definition->key),
                'type' => $definition->type,
                'help' => $definition->help === null ? null : self::text($definition->help, $definition->key),
                'options' => $this->options($definition),
                'is_set' => array_key_exists($definition->key, $explicit),
                'value' => $secret ? null : ($explicit[$definition->key] ?? null),
                'effective' => $secret ? null : $this->settings->get($namespace, $definition->key),
                // Ô bí mật (mật khẩu, khoá API) chỉ người có toàn quyền cài đặt sửa được.
                'editable' => ! $secret || $this->canEditSecrets(),
            ];
        }

        return $fields;
    }

    /**
     * Người đang đăng nhập sửa được cấu hình này: `settings.manage`, hoặc quyền của nhóm (Cài đặt hệ thống) / của plugin.
     */
    public function canManage(string $namespace, ?SettingGroup $group = null): bool
    {
        if (Gate::allows(SettingPermissions::ALL)) {
            return true;
        }

        return $namespace === 'core'
            ? $group !== null && Gate::allows(SettingPermissions::forGroup($group))
            : Gate::allows(SettingPermissions::forPlugin($namespace));
    }

    public function canEditSecrets(): bool
    {
        return Gate::allows(SettingPermissions::ALL);
    }

    /** Có ít nhất một nhóm hoặc một plugin sửa được (menu Cài đặt). */
    public function canOpenAny(): bool
    {
        foreach ($this->systemGroups() as $group) {
            if ($this->canManage('core', $group['group'])) {
                return true;
            }
        }

        return array_filter($this->pluginNamespaces(), fn (string $namespace): bool => $this->canManage($namespace)) !== [];
    }

    /**
     * Từng khoá gửi lên phải thuộc nhóm/plugin người dùng sửa được; ô bí mật có giá trị mới cần `settings.manage`.
     *
     * @param  array<string, mixed>  $values
     *
     * @throws AuthorizationException
     */
    public function authorizeSave(string $namespace, array $values): void
    {
        foreach ($this->settings->definitions($namespace) as $definition) {
            if (! array_key_exists($definition->key, $values)) {
                continue;
            }
            if (! $this->canManage($namespace, $namespace === 'core' ? ($definition->group ?? SettingGroup::Other) : null)) {
                throw new AuthorizationException;
            }
            if ($definition->type === 'secret' && ! in_array($values[$definition->key], [null, ''], true) && ! $this->canEditSecrets()) {
                throw new AuthorizationException;
            }
        }
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
            $definition = $definitions[$key] ?? throw ValidationException::withMessages(["values.{$key}" => __('extension::texts.settings_admin.setting_does_not_exist')]);
            if ($definition->type === 'secret' && ($value === null || $value === '')) {
                continue;
            }

            if ($value === null || $value === '') {
                $this->settings->forget($namespace, $key);
                $this->audit->record('settings.reset', 'setting', "{$namespace}.{$key}");

                continue;
            }

            $value = $this->cast($definition, $value);
            if ($definition->rules !== []) {
                $validator = Validator::make(['value' => $value], ['value' => $definition->rules], [], ['value' => self::text($definition->label, $definition->key)]);
                if ($validator->fails()) {
                    throw ValidationException::withMessages(["values.{$key}" => $validator->errors()->first('value')]);
                }
            }
            $this->settings->set($namespace, $key, $value);
            $this->audit->record('settings.updated', 'setting', "{$namespace}.{$key}", [
                'value' => $definition->type === 'secret' ? '***' : $value,
            ]);
        }
    }

    private function cast(SettingDefinition $definition, mixed $value): mixed
    {
        $error = fn (string $message) => ValidationException::withMessages(["values.{$definition->key}" => $message]);

        return match ($definition->type) {
            'int' => filter_var($value, FILTER_VALIDATE_INT) !== false ? (int) $value : throw $error(__('extension::texts.settings_admin.must_integer')),
            'media' => filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false ? (int) $value : throw $error(__('extension::texts.settings_admin.choose_image_from_media_library')),
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? throw $error(__('extension::texts.settings_admin.must_true_false')),
            'select' => array_key_exists((string) $value, $this->options($definition)) ? (string) $value : throw $error(__('extension::texts.settings_admin.value_not_list')),
            default => is_scalar($value) && mb_strlen((string) $value) <= 2000 ? (string) $value : throw $error(__('extension::texts.settings_admin.most_2000_characters')),
        };
    }

    /**
     * @return array<string, string>
     */
    private function options(SettingDefinition $definition): array
    {
        if ($definition->optionsFromTag === null) {
            return array_map(fn (mixed $label): string => __((string) $label), $definition->options);
        }

        $options = array_map(fn (mixed $label): string => __((string) $label), $definition->options);
        foreach ($this->extensions->tagged($definition->optionsFromTag) as $implementation) {
            if (method_exists($implementation, 'code')) {
                $code = (string) $implementation->code();
                $options[$code] = method_exists($implementation, 'label') ? (string) $implementation->label() : $code;
            }
        }

        return $options;
    }

    /**
     * Nhãn/help của cấu hình là khoá dịch (ADR-049) — dịch theo ngôn ngữ Admin lúc hiển thị; cấu hình theo ngôn ngữ
     * (khoá kết thúc `.<locale>`) được tham số `:locale`.
     */
    private static function text(string $text, string $key): string
    {
        $locale = str_contains($key, '.') ? substr($key, (int) strrpos($key, '.') + 1) : '';

        return (string) __($text, StoreLocale::isSupported($locale) ? ['locale' => strtoupper($locale)] : []);
    }
}
