<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Deprecated;
use FilesystemIterator;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Shared\Contracts\PluginApi;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionClassConstant;
use ReflectionMethod;

/**
 * Quét tĩnh mã của plugin tìm chỗ dùng API đã ngừng khuyến nghị: class
 * `#[PluginApi(deprecated: …)]`, hằng/method static `#[\Deprecated]`, hook khai báo `deprecated`. Chỉ đọc file — không
 * nạp plugin; gọi method trên đối tượng (`->x()`) không suy được kiểu nên không quét (PHP tự báo khi chạy).
 */
final class DeprecationScanner
{
    public function __construct(private readonly HookRegistry $hooks) {}

    /**
     * @return list<array{feature: string, message: string, at: string}>
     */
    public function scan(string $pluginPath): array
    {
        if (! is_dir($pluginPath)) {
            return [];
        }
        $deprecatedHooks = [];
        foreach ($this->hooks->all() as $definition) {
            if ($definition->deprecated !== null) {
                $deprecatedHooks[$definition->name] = $definition->deprecated;
            }
        }

        $found = [];
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($pluginPath, FilesystemIterator::SKIP_DOTS)) as $file) {
            $path = (string) $file;
            if (! str_ends_with($path, '.php') || str_ends_with($path, '.blade.php') || str_contains($path, '/Tests/')) {
                continue;
            }
            $at = fn (int $line): string => ltrim(substr($path, strlen($pluginPath)), '/').":{$line}";
            foreach ($this->scanFile((string) file_get_contents($path), $deprecatedHooks) as [$feature, $message, $line]) {
                $found[] = ['feature' => $feature, 'message' => $message, 'at' => $at($line)];
            }
        }

        return $found;
    }

    /**
     * @param  array<string, string>  $deprecatedHooks
     * @return list<array{0: string, 1: string, 2: int}>
     */
    private function scanFile(string $code, array $deprecatedHooks): array
    {
        $tokens = array_values(array_filter(token_get_all($code), fn (mixed $token): bool => ! is_array($token) || ! in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)));
        $imports = [];
        foreach ($tokens as $index => $token) {
            if (is_array($token) && $token[0] === T_USE && is_array($tokens[$index + 1] ?? null) && in_array($tokens[$index + 1][0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $class = ltrim($tokens[$index + 1][1], '\\');
                $alias = is_array($tokens[$index + 2] ?? null) && $tokens[$index + 2][0] === T_AS ? $tokens[$index + 3][1] : substr((string) strrchr('\\'.$class, '\\'), 1);
                $imports[$alias] = $class;
            }
        }
        $resolve = fn (string $name): string => str_contains($name, '\\') ? ltrim($name, '\\') : ($imports[$name] ?? $name);

        $found = [];
        foreach ($tokens as $index => $token) {
            if (! is_array($token)) {
                continue;
            }
            if ($token[0] === T_CONSTANT_ENCAPSED_STRING && isset($deprecatedHooks[$hook = trim($token[1], '\'"')])) {
                $found[] = ["hook {$hook}", $deprecatedHooks[$hook], $token[2]];

                continue;
            }
            // Bỏ tên trong câu lệnh `use` — chỉ báo chỗ dùng thật.
            if (! in_array($token[0], [T_STRING, T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true) || (is_array($tokens[$index - 1] ?? null) && $tokens[$index - 1][0] === T_USE)) {
                continue;
            }
            $class = $resolve($token[1]);
            if (! str_starts_with($class, 'Modules\\') || (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class))) {
                continue;
            }
            $reflection = new ReflectionClass($class);
            foreach ($reflection->getAttributes(PluginApi::class) as $attribute) {
                $message = $attribute->newInstance()->deprecated;
                if ($message !== null) {
                    $found[] = [$class, $message, $token[2]];
                }
            }
            if (! is_array($tokens[$index + 1] ?? null) || $tokens[$index + 1][0] !== T_DOUBLE_COLON) {
                continue;
            }
            $member = $tokens[$index + 2] ?? null;
            if (! is_array($member) || $member[0] !== T_STRING) {
                continue;
            }
            $target = match (true) {
                $reflection->hasConstant($member[1]) => new ReflectionClassConstant($class, $member[1]),
                $reflection->hasMethod($member[1]) => new ReflectionMethod($class, $member[1]),
                default => null,
            };
            foreach ($target?->getAttributes(Deprecated::class) ?? [] as $attribute) {
                $deprecated = $attribute->newInstance();
                $found[] = ["{$class}::{$member[1]}", trim(($deprecated->message ?? '').($deprecated->since !== null ? " (từ {$deprecated->since})" : '')), $member[2]];
            }
        }

        return $found;
    }
}
