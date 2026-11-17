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

/** Nợ đã biết của M1 — chỉ được ngắn lại (Identity→AdminNavigation đã trả ở C1, 0.5.3). */
const MODULE_ORDER_DEBT = [];

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

it('P4/R26: mỗi điểm mở rộng có TAG có bộ contract test trong Testing/ và ít nhất một implementation chạy bộ đó', function () {
    $missing = [];
    $tests = '';
    foreach ([base_path('modules'), base_path('custom/plugin'), base_path('tests')] as $root) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)) as $file) {
            if (str_ends_with((string) $file, 'Test.php')) {
                $tests .= file_get_contents((string) $file);
            }
        }
    }
    foreach (glob(base_path('modules/*/Contracts/*.php')) ?: [] as $path) {
        $module = basename(dirname($path, 2));
        $name = basename($path, '.php');
        $interface = "Modules\\{$module}\\Contracts\\{$name}";
        // Chỉ interface tự khai báo TAG (CartContentRule kế thừa TAG của PromotionRule — dùng chung bộ test).
        if (! interface_exists($interface) || ! (new ReflectionClass($interface))->hasConstant('TAG')
            || (new ReflectionClass($interface))->getReflectionConstant('TAG')->getDeclaringClass()->getName() !== $interface) {
            continue;
        }
        $suite = "Modules\\{$module}\\Testing\\{$name}Contract";
        if (! class_exists($suite)) {
            $missing[] = "{$interface}: thiếu {$suite}";
        } elseif (! str_contains($tests, "{$name}Contract::define(")) {
            $missing[] = "{$interface}: chưa implementation nào chạy {$name}Contract::define()";
        }
    }

    expect($missing)->toBe([]);
});

it('C1: module thương mại chỉ dùng API khai báo của Identity/Extension/Tenancy (Contracts, Events, Facades, Domain value type)', function () {
    $modules = require base_path('config/modules.php');
    $offenders = [];
    foreach (array_diff($modules, BOUNDARY_RING0) as $module) {
        foreach (boundaryFiles(base_path("modules/{$module}")) as $path) {
            foreach (boundaryReferences($path) as [$target, $rest, $line]) {
                $layer = explode('\\', $rest)[0];
                if (in_array($target, ['Identity', 'Extension', 'Tenancy'], true) && ! in_array($layer, ['Contracts', 'Events', 'Facades', 'Domain', 'Testing', 'PluginServiceProvider'], true)) {
                    $offenders[] = boundaryRelative($path).":{$line} Modules\\{$target}\\{$rest}";
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('R9/D4: controller không tự truy vấn — không ::query(), DB::, gọi truy vấn tĩnh trên model (type-hint model cho route binding, hàm tiện ích tĩnh vẫn được)', function () {
    $offenders = [];
    foreach (glob(base_path('modules/*/Http/Controllers')) ?: [] as $directory) {
        foreach (boundaryFiles($directory) as $path) {
            $code = (string) file_get_contents($path);
            preg_match_all('/^use (Modules\\\\\w+\\\\Persistence\\\\Models\\\\(\w+));/m', $code, $models);
            $patterns = ['/::query\(\)/', '/\bDB::/'];
            foreach ($models[2] as $model) {
                $patterns[] = '/\b'.$model.'::(query|where\w*|find\w*|first\w*|all|create|update\w*|insert\w*|upsert|destroy|count|pluck|with|withCount|latest|oldest|orderBy\w*|select\w*|exists|doesntExist|sum|max|min|avg)\(/';
            }
            foreach ($patterns as $pattern) {
                if (preg_match_all($pattern, $code, $matches, PREG_OFFSET_CAPTURE) > 0) {
                    foreach ($matches[0] as [$text, $offset]) {
                        $offenders[] = boundaryRelative($path).':'.(substr_count(substr($code, 0, $offset), "\n") + 1)." {$text}";
                    }
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});

it('D5: mỗi module có README.md liệt kê đủ bảng sở hữu (không lệch khỏi migration)', function () {
    $modules = require base_path('config/modules.php');
    $problems = [];
    foreach ($modules as $module) {
        $readme = base_path("modules/{$module}/README.md");
        if (! is_file($readme)) {
            $problems[] = "{$module}: thiếu README.md";

            continue;
        }
        $content = (string) file_get_contents($readme);
        foreach (glob(base_path("modules/{$module}/Persistence/Database/migrations/*.php")) ?: [] as $migration) {
            preg_match_all("/Schema::create\\(\\s*'(\\w+)'/", (string) file_get_contents($migration), $matches);
            foreach ($matches[1] as $table) {
                if (! str_contains($content, "`{$table}`")) {
                    $problems[] = "{$module}: README thiếu bảng `{$table}`";
                }
            }
        }
    }

    expect($problems)->toBe([]);
});
