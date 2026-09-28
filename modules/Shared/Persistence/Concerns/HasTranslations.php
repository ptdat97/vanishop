<?php

declare(strict_types=1);

namespace Modules\Shared\Persistence\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Nội dung đa ngôn ngữ lưu ở bảng <entity>_translations(…, locale, fields).
 * Đọc theo locale hiện tại, fallback về 'vi' rồi bản dịch đầu tiên.
 *
 * Model dùng trait phải khai báo translationModel() và translatableFields().
 *
 * @mixin Model
 */
trait HasTranslations
{
    public const FALLBACK_LOCALE = 'vi';

    /**
     * @return class-string<Model>
     */
    abstract protected function translationModel(): string;

    /**
     * @return list<string>
     */
    abstract public function translatableFields(): array;

    public function translations(): HasMany
    {
        return $this->hasMany($this->translationModel(), $this->translationForeignKey());
    }

    /**
     * Khoá ngoại trên bảng bản dịch; null = theo quy ước Laravel (<model>_id).
     */
    protected function translationForeignKey(): ?string
    {
        return null;
    }

    public function translate(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $translations = $this->translations;

        $translation = $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', self::FALLBACK_LOCALE)
            ?? $translations->first();

        $value = $translation?->getAttribute($field);

        return $value === null ? null : (string) $value;
    }

    /**
     * Ghi đè toàn bộ bản dịch: locale không có trong $byLocale sẽ bị xoá.
     *
     * @param  array<string, array<string, mixed>>  $byLocale  locale => [field => value]
     */
    public function syncTranslations(array $byLocale): void
    {
        $fields = $this->translatableFields();

        $this->translations()->whereNotIn('locale', array_keys($byLocale))->delete();

        foreach ($byLocale as $locale => $values) {
            $this->translations()->updateOrCreate(
                ['locale' => $locale],
                array_intersect_key($values, array_flip($fields)),
            );
        }

        $this->unsetRelation('translations');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function translationsByLocale(): array
    {
        $fields = $this->translatableFields();

        return $this->translations
            ->mapWithKeys(fn (Model $translation): array => [$translation->getAttribute('locale') => $translation->only($fields)])
            ->all();
    }
}
