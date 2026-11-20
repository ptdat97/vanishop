<?php

/*
| Đổi code plugin phải tăng `version` trong vanishop.json (rà soát kiến trúc 2026-11-20 — 17 plugin từng quên tăng mà không
| ai phát hiện). Mỗi plugin lưu version + dấu vân tay code (mọi file trừ Tests/ và *.md) trong plugin-versions.snapshot;
| code đổi mà version giữ nguyên → đỏ. Cập nhật snapshot sau khi tăng version: VANI_UPDATE_API_SNAPSHOT=1.
*/

/**
 * @return array<string, array{version: string, hash: string}> plugin id => version + hash code
 */
function pluginFingerprints(): array
{
    $result = [];
    foreach (glob(base_path('custom/plugin/*/vanishop.json')) ?: [] as $manifestPath) {
        $dir = dirname($manifestPath);
        $manifest = json_decode((string) file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            $relative = substr((string) $file->getPathname(), strlen($dir) + 1);
            if ($file->isFile() && ! str_starts_with($relative, 'Tests/') && ! str_ends_with($relative, '.md') && $relative !== 'vanishop.json') {
                $files[] = $relative;
            }
        }
        sort($files);
        $hash = hash_init('sha256');
        foreach ($files as $relative) {
            hash_update($hash, $relative."\0".str_replace("\r\n", "\n", (string) file_get_contents($dir.'/'.$relative))."\0");
        }
        $result[(string) $manifest['id']] = ['version' => (string) $manifest['version'], 'hash' => substr(hash_final($hash), 0, 16)];
    }
    ksort($result);

    return $result;
}

it('plugin đổi code thì phải tăng version (plugin-versions.snapshot)', function () {
    $snapshotPath = __DIR__.'/plugin-versions.snapshot';
    $current = pluginFingerprints();
    $recorded = [];
    foreach (is_file($snapshotPath) ? file($snapshotPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] : [] as $line) {
        [$id, $version, $hash] = explode(' ', $line);
        $recorded[$id] = ['version' => $version, 'hash' => $hash];
    }

    // Kiểm trước khi ghi: chế độ cập nhật snapshot không được che việc quên tăng version.
    $forgotten = [];
    foreach ($current as $id => $row) {
        $before = $recorded[$id] ?? null;
        if ($before !== null && $before['hash'] !== $row['hash'] && version_compare($row['version'], $before['version'], '<=')) {
            $forgotten[] = "{$id} (vẫn {$row['version']})";
        }
    }
    expect($forgotten)->toBe([], 'Plugin đổi code nhưng chưa tăng version trong vanishop.json: '.implode(', ', $forgotten));

    if (getenv('VANI_UPDATE_API_SNAPSHOT') === '1' || $recorded === []) {
        file_put_contents($snapshotPath, implode("\n", array_map(fn (string $id, array $row): string => "{$id} {$row['version']} {$row['hash']}", array_keys($current), $current))."\n");
        $recorded = $current;
    }
    expect(array_map(fn (array $row): string => "{$row['version']} {$row['hash']}", $current))
        ->toBe(array_map(fn (array $row): string => "{$row['version']} {$row['hash']}", $recorded), 'Đã tăng version — cập nhật snapshot: VANI_UPDATE_API_SNAPSHOT=1');
});
