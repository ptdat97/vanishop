<?php

/*
| Bề mặt hợp đồng Storefront API v1 (ADR-044, stabilization S0.3): mọi thao tác, mã trạng thái và từng trường của
| response (kiểu, bắt buộc, enum — đã giải `$ref`) trong docs/api/openapi/storefront-v1.json được chụp vào
| storefront-api.snapshot. StorefrontApiContractTest giữ code khớp OpenAPI; test này giữ OpenAPI không đổi âm thầm.
|
| Đổi hợp đồng → test đỏ. Khi thay đổi là có chủ đích:
|   1. xếp loại (thêm trường/thao tác = minor; xoá, đổi kiểu, thêm bắt buộc ở đầu vào = phá vỡ — với 0.x: tăng số giữa),
|      tăng `vanishop.version` và ghi docs/04-extension/CHANGELOG-extension.md
|   2. chạy lại với VANI_UPDATE_API_SNAPSHOT=1 để ghi snapshot mới
*/

/**
 * Trải phẳng một schema (giải `$ref` tương đối) thành các dòng `đường_dẫn: kiểu [bắt buộc] [enum]`.
 *
 * @param  array<string, mixed>  $schema
 * @return list<string>
 */
function storefrontSchemaLines(array $schema, string $dir, string $at, bool $required = true): array
{
    if (isset($schema['$ref'])) {
        $file = $dir.'/'.$schema['$ref'];

        return storefrontSchemaLines(json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR), dirname($file), $at, $required);
    }

    $line = $at.': '.implode('|', (array) ($schema['type'] ?? 'any'));
    if (isset($schema['format'])) {
        $line .= " ({$schema['format']})";
    }
    if (! $required) {
        $line .= ' optional';
    }
    if (isset($schema['enum'])) {
        $line .= ' enum['.implode(',', array_map('strval', $schema['enum'])).']';
    }
    if (($schema['additionalProperties'] ?? null) === false) {
        $line .= ' closed';
    }
    $lines = [$line];

    $requiredKeys = (array) ($schema['required'] ?? []);
    $properties = (array) ($schema['properties'] ?? []);
    ksort($properties);
    foreach ($properties as $name => $property) {
        array_push($lines, ...storefrontSchemaLines($property, $dir, "{$at}.{$name}", in_array($name, $requiredKeys, true)));
    }
    if (is_array($schema['items'] ?? null)) {
        array_push($lines, ...storefrontSchemaLines($schema['items'], $dir, "{$at}[]"));
    }

    return $lines;
}

/**
 * @return list<string>
 */
function storefrontApiSurface(): array
{
    $openApiDir = base_path('docs/api/openapi');
    $spec = json_decode((string) file_get_contents("{$openApiDir}/storefront-v1.json"), true, flags: JSON_THROW_ON_ERROR);
    $paths = $spec['paths'];
    ksort($paths);

    $lines = [];
    foreach ($paths as $path => $operations) {
        ksort($operations);
        foreach ($operations as $method => $operation) {
            $responses = $operation['responses'];
            ksort($responses);
            $lines[] = strtoupper($method).' '.$path.' security['.implode(',', array_map(fn (array $scheme): string => implode('+', array_keys($scheme)) ?: 'none', $operation['security'] ?? [])).']';
            foreach ($responses as $status => $response) {
                if (isset($response['$ref'])) {
                    $response = $spec['components']['responses'][basename($response['$ref'])];
                }
                $schema = $response['content']['application/json']['schema'] ?? null;
                $lines[] = "  {$status}".($schema === null ? ' (không nội dung)' : '');
                foreach ($schema === null ? [] : storefrontSchemaLines($schema, $openApiDir, '$') as $field) {
                    $lines[] = '    '.$field;
                }
            }
        }
    }

    return $lines;
}

it('bề mặt hợp đồng Storefront API khớp snapshot; đổi hợp đồng phải tăng version và ghi CHANGELOG', function () {
    $snapshot = __DIR__.'/storefront-api.snapshot';
    $version = (string) config('vanishop.version');
    $surface = implode("\n", storefrontApiSurface())."\n";
    $recorded = is_file($snapshot) ? (string) file_get_contents($snapshot) : '';
    [$recordedHeader, $recordedSurface] = array_pad(explode("\n", $recorded, 2), 2, '');

    if (getenv('VANI_UPDATE_API_SNAPSHOT') === '1' || $recorded === '') {
        if ($recorded !== '' && $recordedSurface !== $surface) {
            expect($recordedHeader)->not->toBe("Storefront API v1 — VaniShop {$version}", 'Hợp đồng đổi: tăng vanishop.version trước khi ghi snapshot');
        }
        file_put_contents($snapshot, "Storefront API v1 — VaniShop {$version}\n".$surface);
        [$recordedHeader, $recordedSurface] = explode("\n", (string) file_get_contents($snapshot), 2);
    }

    expect($recordedSurface)->toBe($surface, 'Hợp đồng Storefront API đổi: tăng version, ghi CHANGELOG-extension, chạy lại với VANI_UPDATE_API_SNAPSHOT=1');

    $changedIn = substr($recordedHeader, strlen('Storefront API v1 — VaniShop '));
    expect(str_contains((string) file_get_contents(base_path('docs/04-extension/CHANGELOG-extension.md')), "## {$changedIn} "))
        ->toBeTrue("CHANGELOG-extension thiếu mục cho version {$changedIn} — nơi ghi thay đổi hợp đồng");
});
