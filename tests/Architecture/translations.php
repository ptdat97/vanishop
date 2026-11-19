<?php

use Illuminate\Support\Arr;

/*
| Quét chuỗi dịch cho TranslationKeysTest (ADR-049). File hỗ trợ, nạp bằng require_once.
*/

const TRANSLATION_VIETNAMESE = 'àáảãạăằắẳẵặâầấẩẫậđèéẻẽẹêềếểễệìíỉĩịòóỏõọôồốổỗộơờớởỡợùúủũụưừứửữựỳýỷỹỵÀÁẢÃẠĂẰẮẲẴẶÂẦẤẨẪẬĐÈÉẺẼẸÊỀẾỂỄỆÌÍỈĨỊÒÓỎÕỌÔỒỐỔỖỘƠỜỚỞỠỢÙÚỦŨỤƯỪỨỬỮỰỲÝỶỸỴ';

/** Nhóm lang gốc của Laravel (`lang/<locale>/*.php`) — không có namespace. */
const TRANSLATION_ROOT_GROUPS = ['auth', 'pagination', 'passwords', 'validation'];

/** Khoá đầy đủ: `namespace::nhóm` (cả nhóm) hoặc `namespace::nhóm.khoá…`. */
const TRANSLATION_KEY = '/^[a-z0-9-]+::[a-z0-9_]+(\.[A-Za-z0-9_\-]+)*$/';

/** Phần tĩnh của khoá ghép động: `namespace::nhóm.` (có thể thêm đoạn). */
const TRANSLATION_DYNAMIC_PREFIX = '/^[a-z0-9-]+::[a-z0-9_]+\.([A-Za-z0-9_\-]+\.)*[A-Za-z0-9_\-]*$/';

/**
 * Lời gọi dịch PHP/Blade: `__()`, `trans()`, `trans_choice()`, `@lang`, `@choice` (không tính method `->trans()`).
 * Nhóm 2 = literal, nhóm 3 = ký tự ngay sau literal (`.` = ghép chuỗi).
 */
const TRANSLATION_PHP_CALL = '/(?<![\w>$:\\\\])(?:__|trans|trans_choice|@lang|@choice)\(\s*([\'"])((?:\\\\.|(?!\1).)*)\1\s*(\.?)/su';

/** Lời gọi dịch Vue/TS: `t()`, `tc()` (không tính method `x.t()`). */
const TRANSLATION_JS_CALL = '/(?<![\w.$])tc?\(\s*([\'"`])((?:\\\\.|(?!\1).)*)\1\s*(\+?)/su';

function translationRoot(): string
{
    return str_replace('\\', '/', (string) realpath(__DIR__.'/../..'));
}

function translationRelative(string $path): string
{
    return substr(str_replace('\\', '/', $path), strlen(translationRoot()) + 1);
}

/**
 * File nguồn cần quét: PHP (gồm Blade), Vue/TS/JS. Bỏ vendor, test, lang, database, build.
 *
 * @return list<string> đường dẫn tương đối, `/`
 */
function translationSourceFiles(): array
{
    static $files = null;
    if ($files !== null) {
        return $files;
    }
    $root = translationRoot();
    $skip = '#/(vendor|node_modules|Tests|tests|lang|database|Database|dist|build)/#';
    $files = [];
    foreach (['modules', 'custom', 'app', 'resources', 'routes'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (preg_match($skip, substr($path, strlen($root))) === 1 || preg_match('/\.(php|vue|ts|js|mjs)$/', $path) !== 1) {
                continue;
            }
            $files[] = substr($path, strlen($root) + 1);
        }
    }
    sort($files);

    return $files;
}

/**
 * Namespace dịch → thư mục lang (chứa `<locale>/<nhóm>.php`). Theme: `theme` → mọi theme (theme sở hữu khoá tra theo chuỗi).
 *
 * @return array<string, list<string>>
 */
function translationNamespaces(): array
{
    static $map = null;
    if ($map !== null) {
        return $map;
    }
    $root = translationRoot();
    $map = [];
    foreach (glob("{$root}/modules/*/resources/lang", GLOB_ONLYDIR) as $dir) {
        $map[strtolower(basename(dirname($dir, 2)))] = [str_replace('\\', '/', $dir)];
    }
    foreach (glob("{$root}/custom/plugin/*/*ServiceProvider.php") as $provider) {
        if (preg_match_all("/->translations\\(\\s*\\\$this->pluginPath\\('([^']+)'\\),\\s*'([^']+)'\\)/", (string) file_get_contents($provider), $found, PREG_SET_ORDER)) {
            foreach ($found as [, $path, $namespace]) {
                $map[$namespace] = [str_replace('\\', '/', dirname($provider)).'/'.$path];
            }
        }
    }
    // vani-base trước: theme con chỉ ghi đè.
    $themes = glob("{$root}/custom/theme/*/lang", GLOB_ONLYDIR);
    usort($themes, fn (string $a, string $b): int => (str_contains($b, '/vani-base/') <=> str_contains($a, '/vani-base/')));
    $map['theme'] = array_map(fn (string $dir): string => str_replace('\\', '/', $dir), $themes);

    return $map;
}

/**
 * @return array<string, mixed>
 */
function translationGroup(string $dir, string $locale, string $group): ?array
{
    static $cache = [];
    $file = "{$dir}/{$locale}/{$group}.php";

    return $cache[$file] ??= is_file($file) ? (array) require $file : null;
}

/**
 * Khoá có trong `$locale` của namespace sở hữu (theme: bất kỳ theme nào).
 */
function translationKeyExists(string $key, string $locale): bool
{
    [$namespace, $rest] = explode('::', $key, 2);
    $segments = explode('.', $rest);
    $group = array_shift($segments);
    foreach (translationNamespaces()[$namespace] ?? [] as $dir) {
        $lines = translationGroup($dir, $locale, $group);
        if ($lines === null) {
            continue;
        }
        if ($segments === [] || Arr::has($lines, implode('.', $segments))) {
            return true;
        }
    }

    return false;
}

/**
 * Lời gọi dịch có literal trong một file.
 *
 * @return list<array{key: string, dynamic: bool, js: bool}>
 */
function translationCalls(string $relative): array
{
    $source = (string) file_get_contents(translationRoot().'/'.$relative);
    $js = preg_match('/\.(vue|ts|js|mjs)$/', $relative) === 1;
    // JS của theme tra chuỗi theo id trong `data-vani-i18n` (giá trị đã dịch ở layout) — không phải khoá dịch.
    if ($js && str_starts_with($relative, 'custom/theme/')) {
        return [];
    }
    $calls = [];
    preg_match_all($js ? TRANSLATION_JS_CALL : TRANSLATION_PHP_CALL, $source, $matches, PREG_SET_ORDER);
    foreach ($matches as $match) {
        $literal = str_replace(["\\'", '\\"'], ["'", '"'], $match[2]);
        $interpolated = ($match[1] === '"' || $match[1] === '`') && preg_match('/\$\{|\{\$|\$[a-zA-Z_]/', $literal) === 1;
        $dynamic = $interpolated || $match[3] !== '';
        if ($interpolated) {
            $literal = (string) preg_split('/\$\{|\{\$|\$[a-zA-Z_]/', $literal)[0];
        }
        $calls[] = ['key' => $literal, 'dynamic' => $dynamic, 'js' => $js];
    }

    return $calls;
}

function translationKeyIsValid(string $key, bool $dynamic): bool
{
    if (in_array(strtok($key, '.'), TRANSLATION_ROOT_GROUPS, true) && ! str_contains($key, '::')) {
        return true;
    }

    return preg_match($dynamic ? TRANSLATION_DYNAMIC_PREFIX : TRANSLATION_KEY, $key) === 1
        || ($dynamic && preg_match(TRANSLATION_KEY, rtrim($key, '.')) === 1);
}

/**
 * Luật 1: số lời gọi dịch có literal không phải khoá có namespace, theo file.
 *
 * @return array<string, int>
 */
function translationRawCallOffenders(): array
{
    $offenders = [];
    foreach (translationSourceFiles() as $file) {
        $count = count(array_filter(translationCalls($file), fn (array $call): bool => ! translationKeyIsValid($call['key'], $call['dynamic'])));
        if ($count > 0) {
            $offenders[$file] = $count;
        }
    }

    return $offenders;
}

/**
 * Luật 2: khoá tĩnh có namespace nhưng thiếu ở `vi` hoặc `en`.
 *
 * @return list<string> "file: khoá (locale)"
 */
function translationMissingKeys(): array
{
    $missing = [];
    foreach (translationSourceFiles() as $file) {
        foreach (translationCalls($file) as $call) {
            if ($call['dynamic'] || ! str_contains($call['key'], '::') || preg_match(TRANSLATION_KEY, $call['key']) !== 1) {
                continue;
            }
            foreach (['vi', 'en'] as $locale) {
                if (! translationKeyExists($call['key'], $locale)) {
                    $missing[] = "{$file}: {$call['key']} ({$locale})";
                }
            }
        }
    }

    return array_values(array_unique($missing));
}

/**
 * @param  array<string, mixed>  $lines
 * @return array<string, string>
 */
function translationFlatten(array $lines, string $prefix = ''): array
{
    $flat = [];
    foreach ($lines as $key => $value) {
        if (is_array($value)) {
            $flat += translationFlatten($value, "{$prefix}{$key}.");
        } else {
            $flat["{$prefix}{$key}"] = (string) $value;
        }
    }

    return $flat;
}

/**
 * Luật 3: `vi` và `en` cùng tập khoá, cùng tham số `:name`, theo thư mục lang.
 *
 * @return list<string>
 */
function translationParityProblems(): array
{
    $problems = [];
    $dirs = array_unique(array_merge(...array_values(translationNamespaces())));
    foreach ($dirs as $dir) {
        $groups = array_unique(array_map('basename', [...glob("{$dir}/vi/*.php"), ...glob("{$dir}/en/*.php")]));
        foreach ($groups as $file) {
            $group = basename($file, '.php');
            $vi = translationFlatten(translationGroup($dir, 'vi', $group) ?? []);
            $en = translationFlatten(translationGroup($dir, 'en', $group) ?? []);
            $where = translationRelative($dir)."/*/{$group}.php";
            foreach (array_diff_key($vi, $en) as $key => $_) {
                $problems[] = "{$where}: {$key} thiếu ở en";
            }
            foreach (array_diff_key($en, $vi) as $key => $_) {
                $problems[] = "{$where}: {$key} thiếu ở vi";
            }
            foreach (array_intersect_key($vi, $en) as $key => $text) {
                preg_match_all('/(?<![:\w]):([a-z_]+)/', $text, $viParams);
                preg_match_all('/(?<![:\w]):([a-z_]+)/', $en[$key], $enParams);
                $viParams = array_unique($viParams[1]);
                $enParams = array_unique($enParams[1]);
                sort($viParams);
                sort($enParams);
                if ($viParams !== $enParams) {
                    $problems[] = "{$where}: {$key} tham số khác nhau (vi: ".implode(',', $viParams).'; en: '.implode(',', $enParams).')';
                }
            }
        }
    }
    sort($problems);

    return $problems;
}

/**
 * Luật 4: bản dịch JSON (khoá = câu) — file `lang/*.json` và lời gọi nạp JSON.
 *
 * @return list<string>
 */
function translationJsonUsages(): array
{
    $root = translationRoot();
    $found = [];
    foreach (['modules', 'custom', 'app', 'resources'] as $dir) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}", FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $path = str_replace('\\', '/', $file->getPathname());
            if (preg_match('#/(vendor|node_modules)/#', $path) === 1) {
                continue;
            }
            if (preg_match('#/lang/[^/]+\.json$#', $path) === 1) {
                $found[] = translationRelative($path);
            }
        }
    }
    foreach (translationSourceFiles() as $file) {
        // Loader bọc của theme phải cài `addJsonPath()` của interface `Loader` (chỉ chuyển tiếp cho loader gốc).
        if ($file === 'modules/Storefront/Application/Theme/ThemeTranslationLoader.php') {
            continue;
        }
        if (str_ends_with($file, '.php') && preg_match('/\b(loadJsonTranslationsFrom|addJsonPath)\(/', (string) file_get_contents("{$root}/{$file}")) === 1) {
            $found[] = $file;
        }
    }
    sort($found);

    return $found;
}

/**
 * Vùng không dịch (ADR-049 quyết định 6): lệnh CLI, health-check, script dev.
 */
function translationExemptFile(string $file): bool
{
    return preg_match('#(^|/)Console/|HealthCheck[^/]*\.php$|^custom/plugin/HelloWorld/|/config/|^app/Console/#', $file) === 1;
}

/** Dòng PHP không dịch: log, exception cho dev, mô tả lệnh. */
const TRANSLATION_EXEMPT_LINE = '/\b(Log::|logger\(|->(debug|info|notice|warning|error|critical|alert|emergency)\(|throw new \\\\?(RuntimeException|LogicException|InvalidArgumentException|UnexpectedValueException|DomainException|OutOfBoundsException|LengthException)\b|\$(signature|description)\s*=)/';

/**
 * Luật 5: chữ tiếng Việt ngoài lời gọi dịch, đếm theo file.
 *
 * @return array<string, int>
 */
function translationHardcodedOffenders(): array
{
    $vietnamese = '/['.TRANSLATION_VIETNAMESE.']/u';
    $offenders = [];
    foreach (translationSourceFiles() as $file) {
        if (translationExemptFile($file)) {
            continue;
        }
        $source = (string) file_get_contents(translationRoot().'/'.$file);
        if (preg_match($vietnamese, $source) !== 1) {
            continue;
        }
        $count = 0;
        if (str_ends_with($file, '.blade.php')) {
            $source = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $source);
            $source = (string) preg_replace(TRANSLATION_PHP_CALL, '', $source);
            $source = (string) preg_replace('#<!--.*?-->|/\*.*?\*/#s', '', $source);
            foreach (explode("\n", $source) as $line) {
                $line = (string) preg_replace('#(?<![:\'"])//.*$#', '', $line);
                $count += preg_match($vietnamese, $line) === 1 ? 1 : 0;
            }
        } elseif (str_ends_with($file, '.php')) {
            $lines = explode("\n", $source);
            $tokens = token_get_all($source);
            foreach ($tokens as $index => $token) {
                if (! is_array($token) || ! in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_ENCAPSED_AND_WHITESPACE, T_INLINE_HTML], true) || preg_match($vietnamese, $token[1]) !== 1) {
                    continue;
                }
                if (preg_match(TRANSLATION_EXEMPT_LINE, $lines[$token[2] - 1] ?? '') === 1 || translationTokenIsCallArgument($tokens, $index)) {
                    continue;
                }
                $count++;
            }
        } else {
            $source = (string) preg_replace('#<!--.*?-->|/\*.*?\*/#s', '', $source);
            $source = (string) preg_replace(TRANSLATION_JS_CALL, '', $source);
            foreach (explode("\n", $source) as $line) {
                $line = (string) preg_replace('#(?<![:\'"`])//.*$#', '', $line);
                // Thông điệp cho dev ở console trình duyệt — không dịch.
                $count += preg_match($vietnamese, $line) === 1 && preg_match('/\bconsole\.(log|info|warn|error|debug)\(|\bnew Error\(/', $line) !== 1 ? 1 : 0;
            }
        }
        if ($count > 0) {
            $offenders[$file] = $count;
        }
    }

    return $offenders;
}

/**
 * Token chuỗi là đối số đầu của `__()` / `trans()` / `trans_choice()` (đã tính ở luật 1).
 *
 * @param  array<int, mixed>  $tokens
 */
function translationTokenIsCallArgument(array $tokens, int $index): bool
{
    for ($i = $index - 1; $i >= 0; $i--) {
        $token = $tokens[$i];
        if (is_array($token) && $token[0] === T_WHITESPACE) {
            continue;
        }
        if ($token !== '(') {
            return false;
        }
        for ($j = $i - 1; $j >= 0; $j--) {
            $name = $tokens[$j];
            if (is_array($name) && $name[0] === T_WHITESPACE) {
                continue;
            }

            return is_array($name) && $name[0] === T_STRING && in_array($name[1], ['__', 'trans', 'trans_choice'], true);
        }
    }

    return false;
}
