<?php

/*
| Ranh giới module (architecture-consolidation K1 — M1, M4, M5; R7, R11).
|
| Quét token tên class trong mọi file .php (class, route, hook, helper) — không chỉ class như arch() của Pest.
|   M4/R7: module chỉ dùng Contracts/Events của module thương mại khác (mọi module, mọi tầng kể cả Http).
|   M1:    module chỉ dùng Contracts của module đứng TRƯỚC nó trong config/modules.php; Events nghe được từ mọi module.
|   M5/R11: bảng thuộc module/plugin tạo ra nó (Schema::create); module/plugin khác không DB::table/join/exists: bảng đó.
*/

const BOUNDARY_RING0 = ['Shared', 'Tenancy', 'Identity', 'Extension'];

/**
 * Nợ đã biết của M1 — chỉ được ngắn lại. Identity (vòng 0) dùng registry menu Admin của Extension: xử lý ở C1 (đợt K4,
 * chuyển AdminNavigation vào Identity\Contracts).
 */
const MODULE_ORDER_DEBT = [
    'Identity -> Extension\\Application\\Admin\\AdminNavigation',
];

/**
 * @return iterable<string> đường dẫn file .php (không gồm Blade, Tests)
 */
function boundaryFiles(string $directory): iterable
{
    if (! is_dir($directory)) {
        return;
    }
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = (string) $file;
        if (str_ends_with($path, '.php') && ! str_ends_with($path, '.blade.php') && ! str_contains($path, '/Tests/')) {
            yield $path;
        }
    }
}

/**
 * Tham chiếu `Modules\<Module>\<Layer>\...` trong file: [module, phần sau module, dòng].
 *
 * @return list<array{0: string, 1: string, 2: int}>
 */
function boundaryReferences(string $path): array
{
    $references = [];
    foreach (token_get_all((string) file_get_contents($path)) as $token) {
        if (! is_array($token) || ! in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
            continue;
        }
        $segments = explode('\\', ltrim($token[1], '\\'));
        if ($segments[0] === 'Modules' && count($segments) >= 3) {
            $references[] = [$segments[1], implode('\\', array_slice($segments, 2)), $token[2]];
        }
    }

    return $references;
}

function boundaryRelative(string $path): string
{
    return substr($path, strlen(base_path()) + 1);
}

it('M4/R7: module chỉ dùng Contracts/Events của module thương mại khác (mọi module, mọi tầng)', function () {
    $modules = require base_path('config/modules.php');
    $commerce = array_values(array_diff($modules, BOUNDARY_RING0));
    $offenders = [];
    foreach ($modules as $module) {
        foreach (boundaryFiles(base_path("modules/{$module}")) as $path) {
            foreach (boundaryReferences($path) as [$target, $rest, $line]) {
                $layer = explode('\\', $rest)[0];
                if ($target !== $module && in_array($target, $commerce, true) && ! in_array($layer, ['Contracts', 'Events'], true)) {
                    $offenders[] = boundaryRelative($path).":{$line} Modules\\{$target}\\{$rest}";
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('M1: module chỉ dùng Contracts của module đứng trước trong config/modules.php (Events thì từ mọi module)', function () {
    $modules = require base_path('config/modules.php');
    $position = array_flip($modules);
    $found = [];
    $where = [];
    foreach ($modules as $module) {
        foreach (boundaryFiles(base_path("modules/{$module}")) as $path) {
            foreach (boundaryReferences($path) as [$target, $rest, $line]) {
                if (! isset($position[$target]) || $position[$target] <= $position[$module] || str_starts_with($rest, 'Events\\')) {
                    continue;
                }
                $key = "{$module} -> {$target}\\{$rest}";
                $found[$key] = true;
                $where[$key] = boundaryRelative($path).":{$line}";
            }
        }
    }

    $new = array_values(array_diff(array_keys($found), MODULE_ORDER_DEBT));
    expect(array_map(fn (string $key): string => "{$key} ({$where[$key]})", $new))->toBe([])
        ->and(array_values(array_diff(MODULE_ORDER_DEBT, array_keys($found))))->toBe([], 'Nợ đã trả — xoá khỏi MODULE_ORDER_DEBT.');
});

it('M5/R11: chỉ module/plugin sở hữu bảng mới truy vấn bảng đó', function () {
    $owners = [];
    $sources = [];
    foreach (glob(base_path('modules/*'), GLOB_ONLYDIR) as $directory) {
        $sources['module:'.basename($directory)] = $directory;
    }
    foreach (glob(base_path('custom/plugin/*'), GLOB_ONLYDIR) as $directory) {
        $sources['plugin:'.basename($directory)] = $directory;
    }
    foreach ($sources as $owner => $directory) {
        foreach (boundaryFiles($directory) as $path) {
            if (str_contains($path, 'migrations/')) {
                preg_match_all("/Schema::create\\(\\s*'(\\w+)'/", (string) file_get_contents($path), $matches);
                foreach ($matches[1] as $table) {
                    $owners[$table] = $owner;
                }
            }
        }
    }
    expect($owners)->not->toBeEmpty();

    $offenders = [];
    foreach ($sources as $owner => $directory) {
        foreach (boundaryFiles($directory) as $path) {
            if (str_contains($path, 'migrations/') || str_contains($path, '/Database/')) {
                continue;
            }
            $code = (string) file_get_contents($path);
            preg_match_all("/(?:DB::table|join|leftJoin|rightJoin|from|whereExists)\\(\\s*'(\\w+)'|(?:exists|unique):(\\w+)|Rule::(?:exists|unique)\\(\\s*'(\\w+)'/", $code, $matches, PREG_SET_ORDER);
            foreach ($matches as $match) {
                $table = $match[1] ?: ($match[2] ?? '') ?: ($match[3] ?? '');
                if (isset($owners[$table]) && $owners[$table] !== $owner) {
                    $offenders[] = boundaryRelative($path)." {$table} (của {$owners[$table]})";
                }
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});
