<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Theme;

use Closure;
use Illuminate\Contracts\Translation\Loader;

/**
 * Chuỗi dịch của theme (ADR-049): namespace `theme::` gộp `<theme>/lang/<locale>/<nhóm>.php` theo chuỗi theme cha → con
 * (theme con ghi đè từng khoá). Namespace khác chuyển cho loader gốc của Laravel.
 *
 * Chuỗi theme lấy lười từ `$paths` lần đầu cần; `refresh()` (middleware `vani.theme`, mỗi request) tính lại — theme có thể
 * đổi giữa các request cùng tiến trình (Octane, queue, test).
 */
final class ThemeTranslationLoader implements Loader
{
    public const NAMESPACE = 'theme';

    /** @var list<string>|null thư mục `lang` theo thứ tự cha → con */
    private ?array $themePaths = null;

    /**
     * @param  Closure(): list<string>  $paths  thư mục `lang` của chuỗi theme đang hoạt động, cha → con
     */
    public function __construct(private readonly Loader $inner, private readonly Closure $paths) {}

    /**
     * Tính lại chuỗi theme; true khi đổi (người gọi xoá cache dòng đã nạp của translator).
     */
    public function refresh(): bool
    {
        $previous = $this->themePaths;
        $this->themePaths = ($this->paths)();

        return $previous !== null && $previous !== $this->themePaths;
    }

    /**
     * Đặt thẳng chuỗi theme (test, công cụ).
     *
     * @param  list<string>  $paths
     */
    public function usePaths(array $paths): void
    {
        $this->themePaths = $paths;
    }

    /**
     * @param  string  $locale
     * @param  string  $group
     * @param  string|null  $namespace
     * @return array<string, mixed>
     */
    public function load($locale, $group, $namespace = null): array
    {
        if ($namespace !== self::NAMESPACE) {
            return $this->inner->load($locale, $group, $namespace);
        }
        if ($group === '*') {
            return [];
        }

        $lines = [];
        foreach ($this->themePaths ??= ($this->paths)() as $path) {
            $file = "{$path}/{$locale}/{$group}.php";
            if (is_file($file)) {
                $lines = array_replace_recursive($lines, (array) require $file);
            }
        }

        return $lines;
    }

    /**
     * @param  string  $namespace
     * @param  string  $hint
     */
    public function addNamespace($namespace, $hint): void
    {
        $this->inner->addNamespace($namespace, $hint);
    }

    /**
     * @param  string  $path
     */
    public function addJsonPath($path): void
    {
        $this->inner->addJsonPath($path);
    }

    /**
     * `Translator::addPath()` gọi thẳng loader (ngoài interface `Loader`).
     */
    public function addPath(string $path): void
    {
        if (method_exists($this->inner, 'addPath')) {
            $this->inner->addPath($path);
        }
    }

    /**
     * @return array<string, string>
     */
    public function namespaces(): array
    {
        return $this->inner->namespaces();
    }

    public function inner(): Loader
    {
        return $this->inner;
    }
}
