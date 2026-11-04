<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Shared\Support\StoreLocale;
use Modules\Tenancy\Contracts\Settings;

/**
 * Giá trị cấu hình đầu/chân trang… theo schema theme khai báo (reference-parity §3.16-3). Core không biết trường nào:
 * chỉ validate theo schema và đưa giá trị cho layout (`$themeSettings`). Trường dịch lưu `{locale: giá trị}`, đọc theo
 * ngôn ngữ trang, thiếu thì rơi về ngôn ngữ mặc định.
 */
final class ThemeSettings
{
    private const SETTING = 'storefront.theme_settings.';

    public function __construct(
        private readonly Themes $themes,
        private readonly Settings $settings,
    ) {}

    /**
     * @return array<string, ThemeSettingSection>
     */
    public function sections(): array
    {
        return $this->themes->settingsSchema()['sections'] ?? [];
    }

    /**
     * Giá trị đã lưu (trường dịch là mảng theo ngôn ngữ) — cho form Admin.
     *
     * @return array<string, array<string, mixed>>
     */
    public function stored(): array
    {
        $schema = $this->themes->settingsSchema();
        $stored = $schema === null ? null : $this->settings->get('core', self::SETTING.$schema['theme']);

        return is_array($stored) ? $stored : [];
    }

    /**
     * Giá trị cho layout theo ngôn ngữ: mọi trường của schema đều có khoá (chưa nhập = null, bool = false).
     *
     * @return array<string, array<string, mixed>>
     */
    public function values(string $locale): array
    {
        $stored = $this->stored();
        $values = [];
        foreach ($this->sections() as $code => $section) {
            foreach ($section->fields as $field) {
                $value = $stored[$code][$field->key] ?? null;
                if ($section->isTranslatable($field->key)) {
                    $value = is_array($value) ? (self::filled($value[$locale] ?? null) ? $value[$locale] : ($value[StoreLocale::default()] ?? null)) : null;
                }
                $values[$code][$field->key] = $field->type === 'bool' ? (bool) $value : $value;
            }
        }

        return $values;
    }

    /**
     * Lưu theo schema: bỏ khoá lạ, validate từng trường (trường dịch: từng ngôn ngữ, chỉ ngôn ngữ mặc định theo `required`).
     * Lỗi có khoá `settings.<nhóm>.<trường>[.<ngôn ngữ>]`.
     *
     * @param  array<string, mixed>  $input
     *
     * @throws ValidationException
     */
    public function save(array $input): void
    {
        $schema = $this->themes->settingsSchema() ?? throw ValidationException::withMessages(['settings' => __('storefront::messages.theme_settings_none')]);

        $data = [];
        $rules = [];
        $clean = [];
        foreach ($schema['sections'] as $code => $section) {
            foreach ($section->fields as $field) {
                $raw = $input[$code][$field->key] ?? null;
                if (! $section->isTranslatable($field->key)) {
                    $data[$code][$field->key] = $raw;
                    $rules["{$code}.{$field->key}"] = $section->rules($field);
                    $clean[$code][$field->key] = $this->cast($field, $raw);

                    continue;
                }

                foreach (StoreLocale::supported() as $locale) {
                    $value = is_array($raw) ? ($raw[$locale] ?? null) : null;
                    $data[$code][$field->key][$locale] = $value;
                    $rules["{$code}.{$field->key}.{$locale}"] = $locale === StoreLocale::default() ? $section->rules($field) : self::optional($section->rules($field));
                    $clean[$code][$field->key][$locale] = $this->cast($field, $value);
                }
            }
        }

        $validator = Validator::make($data, $rules);
        if ($validator->fails()) {
            throw ValidationException::withMessages(collect($validator->errors()->messages())->mapWithKeys(fn (array $messages, string $key): array => ["settings.{$key}" => $messages])->all());
        }

        $this->settings->set('core', self::SETTING.$schema['theme'], $clean);
    }

    private function cast(FieldDefinition $field, mixed $value): mixed
    {
        return match ($field->type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOL),
            'int' => is_numeric($value) ? (int) $value : null,
            default => self::filled($value) ? trim((string) $value) : null,
        };
    }

    /**
     * @param  list<mixed>  $rules
     * @return list<mixed>
     */
    private static function optional(array $rules): array
    {
        return array_values(array_map(fn (mixed $rule): mixed => $rule === 'required' ? 'nullable' : $rule, $rules));
    }

    private static function filled(mixed $value): bool
    {
        return is_scalar($value) && trim((string) $value) !== '';
    }
}
