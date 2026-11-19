<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Contracts\Translation\Translator;

/**
 * Bản dịch giao diện Vue của Admin (ADR-049): nhóm `admin` của mọi namespace dịch đã đăng ký (module Core, plugin đang
 * bật), theo ngôn ngữ hiện tại — `['catalog::admin' => [...], 'vani-cms::admin' => [...]]`. Vue tra bằng `t()`/`tc()`.
 */
final class AdminTranslations
{
    public const GROUP = 'admin';

    public function __construct(private readonly Translator $translator) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function forLocale(string $locale): array
    {
        $catalog = [];
        foreach (array_keys($this->translator->getLoader()->namespaces()) as $namespace) {
            $lines = $this->translator->get("{$namespace}::".self::GROUP, [], $locale);
            if (is_array($lines) && $lines !== []) {
                $catalog["{$namespace}::".self::GROUP] = $lines;
            }
        }
        ksort($catalog);

        return $catalog;
    }
}
