<?php

/*
| ADR-033: hạng mục ngoài phạm vi dự án chỉ được nhắc trong chính ADR đó. Tài liệu, code, comment, test không thiết kế,
| ví dụ hay chừa chỗ cho chúng.
|
| Ngoại lệ: README.md tóm tắt phạm vi dự án (cùng vai trò ADR-033) — được nhắc hạng mục ngoài phạm vi ở câu phủ định
| ("không triển khai…", "chưa phải…", "… trước Microservices"), không được mô tả như việc sẽ làm.
*/

/** File được nêu hạng mục ngoài phạm vi, nhưng chỉ ở câu phủ định. */
const SCOPE_NEGATION_ONLY = ['README.md'];

it('hạng mục ngoài phạm vi (ADR-033) không xuất hiện trong docs/ và code', function () {
    $root = realpath(__DIR__.'/../..');
    $banned = '/micro-?services?|marketplace|creator|affiliate|live[ -]?commerce|live[ -]?stream|social commerce|sàn TMĐT|(?<![\w-])sellers?\b/iu';
    $allowed = ['docs/19-adr/ADR-033-out-of-scope.md', 'tests/Architecture/ScopeTest.php'];
    $extensions = ['md', 'json', 'php', 'vue', 'ts', 'js', 'neon', 'yml', 'yaml'];

    $offenders = [];
    $paths = ['docs', 'modules', 'custom', 'app', 'config', 'resources', 'routes', 'database', 'tests', 'README.md', 'AGENTS.md', 'CLAUDE.md'];
    foreach ($paths as $path) {
        $absolute = "{$root}/{$path}";
        $files = is_file($absolute) ? [new SplFileInfo($absolute)] : (is_dir($absolute) ? new RecursiveIteratorIterator(new RecursiveDirectoryIterator($absolute, FilesystemIterator::SKIP_DOTS)) : []);
        foreach ($files as $file) {
            $relative = substr($file->getPathname(), strlen($root) + 1);
            if (! in_array($file->getExtension(), $extensions, true) || in_array($relative, $allowed, true) || str_contains($relative, 'node_modules/')) {
                continue;
            }
            foreach (file($file->getPathname()) ?: [] as $number => $line) {
                $negated = in_array($relative, SCOPE_NEGATION_ONLY, true) && preg_match('/\b(không|chưa|trước)\b/iu', $line) === 1;
                if (! $negated && preg_match($banned, $line, $match) === 1) {
                    $offenders[] = "{$relative}:".($number + 1)." '{$match[0]}'";
                }
            }
        }
    }

    expect($offenders)->toBe([]);
});
