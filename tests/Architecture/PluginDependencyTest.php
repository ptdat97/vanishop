<?php

/*
| Plugin dùng plugin khác (ADR-030 §4.H): chỉ qua Contracts/Events/Testing của plugin đó, và phải khai báo plugin đó
| trong requires.plugins (resolver sắp thứ tự nạp, chặn tắt plugin đang được phụ thuộc).
*/

it('plugin chỉ dùng Contracts/Events/Testing của plugin đã khai báo trong requires.plugins', function () {
    $root = realpath(__DIR__.'/../../custom/plugin');
    $plugins = [];
    foreach (glob("{$root}/*/vanishop.json") as $file) {
        $manifest = json_decode((string) file_get_contents($file), true);
        $plugins[basename(dirname($file))] = ['id' => $manifest['id'], 'requires' => array_keys((array) ($manifest['requires']['plugins'] ?? []))];
    }

    $offenders = [];
    foreach ($plugins as $directory => $plugin) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$directory}", FilesystemIterator::SKIP_DOTS)) as $file) {
            // Test của plugin được bật plugin khác để kiểm tra phối hợp (vd. ZNS dự phòng sang SMS).
            if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Tests/')) {
                continue;
            }
            preg_match_all('/Plugin\\\\(\w+)\\\\(\w+)/', (string) file_get_contents($file->getPathname()), $matches, PREG_SET_ORDER);
            foreach ($matches as [, $other, $layer]) {
                if ($other === $directory || ! isset($plugins[$other])) {
                    continue;
                }
                if (! in_array($plugins[$other]['id'], $plugin['requires'], true) || ! in_array($layer, ['Contracts', 'Events', 'Testing'], true)) {
                    $offenders[] = "{$plugin['id']} dùng Plugin\\{$other}\\{$layer}";
                }
            }
        }
    }

    expect(array_values(array_unique($offenders)))->toBe([]);
});
