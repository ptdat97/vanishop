<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

/**
 * Một theme trong custom/theme/<name>/ (theme.json): tên, nhãn, theme cha, token mặc định, vị trí menu, schema cấu hình.
 */
final readonly class Theme
{
    /**
     * @param  array<string, string>  $tokens  tên token => giá trị CSS (vd. "color-primary" => "#111827")
     * @param  array<string, array{label: string, depth: int}>|null  $menus  vị trí menu theme render; null = dùng của theme cha
     * @param  array<string, ThemeSettingSection>|null  $settings  nhóm cấu hình (đầu/chân trang…); null = dùng của theme cha
     */
    public function __construct(
        public string $name,
        public string $label,
        public ?string $parent,
        public string $path,
        public array $tokens = [],
        public ?array $menus = null,
        public ?array $settings = null,
    ) {}

    public function viewsPath(): string
    {
        return $this->path.'/views';
    }
}
