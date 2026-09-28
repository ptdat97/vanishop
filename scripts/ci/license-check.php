<?php

/*
| Kiểm tra license của dependency PHP production (rule R25, docs/01-principles/clean-room-license.md).
| Dùng: composer licenses --no-dev --format=json | php scripts/ci/license-check.php
*/

$allowed = ['MIT', 'BSD-2-Clause', 'BSD-3-Clause', 'Apache-2.0', 'ISC'];

$report = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$violations = [];

foreach ($report['dependencies'] ?? [] as $package => $info) {
    $licenses = $info['license'] ?? [];
    if (array_intersect($licenses, $allowed) === []) {
        $violations[] = $package.' ('.(implode(', ', $licenses) ?: 'không rõ').')';
    }
}

if ($violations !== []) {
    fwrite(STDERR, "License không được phép:\n - ".implode("\n - ", $violations)."\n");
    exit(1);
}

echo 'License check: OK ('.count($report['dependencies'] ?? []).' packages)'.PHP_EOL;
