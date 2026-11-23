<?php

declare(strict_types=1);

namespace Plugin\Cms\Infrastructure;

use Illuminate\Validation\ValidationException;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Shared\Support\StoreLocale;
use Plugin\Cms\Persistence\CategoryBanner;

final class CategoryBannerForm
{
    public const KEY = 'banner';

    private const TEXT_FIELDS = ['title', 'markdown', 'button_text'];

    public function __construct(private readonly ExtraContent $content) {}

    /** @return list<FieldDefinition> */
    public function fields(): array
    {
        $fields = [
            FieldDefinition::bool('active', 'vani-cms::admin.banners_form.active', help: 'vani-cms::admin.banners_form.category_form_visibility'),
            FieldDefinition::media('desktop_media_id', 'vani-cms::admin.banners_form.desktop_image', help: 'vani-cms::admin.banners_form.suggested_1600_480'),
            FieldDefinition::media('mobile_media_id', 'vani-cms::admin.banners_form.mobile_image_optional', help: 'vani-cms::admin.banners_form.not_chosen_use_desktop_image'),
            FieldDefinition::string('button_url', 'vani-cms::admin.banners_form.button_link', help: 'vani-cms::admin.banners_form.store_path_https', max: 500),
        ];

        foreach (StoreLocale::supported() as $locale) {
            $language = strtoupper($locale);
            $fields[] = FieldDefinition::string(self::localizedKey('title', $locale), __('vani-cms::admin.banners_form.title')." ({$language})", max: 160);
            $fields[] = FieldDefinition::text(self::localizedKey('markdown', $locale), __('vani-cms::admin.banners_form.short_text_markdown')." ({$language})", max: 1000);
            $fields[] = FieldDefinition::string(self::localizedKey('button_text', $locale), __('vani-cms::admin.banners_form.button_text')." ({$language})", max: 60);
        }

        return $fields;
    }

    /** @return array<string, bool|int|string|null> */
    public function load(int $categoryId): array
    {
        return $this->values(CategoryBanner::query()->where('category_id', $categoryId)->first());
    }

    /** @param array<string, mixed> $input */
    public function save(int $categoryId, array $input): void
    {
        if ($input === []) {
            return;
        }

        $banner = CategoryBanner::query()->where('category_id', $categoryId)->first();
        $current = $this->values($banner);
        $values = array_replace($current, $input);
        if ($values === $current || ($banner === null && ! collect($values)->except('active')->contains(fn (mixed $value): bool => filled($value)))) {
            return;
        }

        $localized = [];
        foreach (StoreLocale::supported() as $locale) {
            foreach (self::TEXT_FIELDS as $field) {
                $localized[$locale][$field] = $values[self::localizedKey($field, $locale)];
            }
        }

        try {
            $this->content->saveBanner($banner, $categoryId, [
                'content' => $localized,
                'button_url' => $values['button_url'],
                'desktop_media_id' => isset($values['desktop_media_id']) ? (int) $values['desktop_media_id'] : null,
                'mobile_media_id' => isset($values['mobile_media_id']) ? (int) $values['mobile_media_id'] : null,
                'active' => (bool) $values['active'],
            ]);
        } catch (ValidationException $exception) {
            $errors = collect($exception->errors())->mapWithKeys(fn (array $errors, string $field): array => [
                ($field === 'category_id' ? 'business' : 'extensions.vani-cms.'.self::KEY.'.'.$field) => $errors,
            ]);

            throw ValidationException::withMessages($errors->all());
        }
    }

    /** @return array<string, bool|int|string|null> */
    private function values(?CategoryBanner $banner): array
    {
        $values = [
            'active' => $banner->active ?? false,
            'button_url' => $banner?->button_url,
            'desktop_media_id' => $banner?->desktop_media_id,
            'mobile_media_id' => $banner?->mobile_media_id,
        ];
        foreach (StoreLocale::supported() as $locale) {
            foreach (self::TEXT_FIELDS as $field) {
                $values[self::localizedKey($field, $locale)] = $banner->content[$locale][$field] ?? null;
            }
        }

        return $values;
    }

    private static function localizedKey(string $field, string $locale): string
    {
        return $field.'_'.strtolower(str_replace('-', '_', $locale));
    }
}
