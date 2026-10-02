<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

/**
 * Một theme trong custom/theme/<name>/ (theme.json): tên, nhãn, theme cha, token mặc định.
 */
final readonly class Theme
{
    /**
     * @param  array<string, string>  $tokens  tên token => giá trị CSS (vd. "color-primary" => "#111827")
     */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $parent,
        public string $path,
        public array $tokens = [],
    ) {}

    public function viewsPath(): string
    {
        return $this->path.'/views';
    }
}
