<?php

use Modules\Extension\PluginServiceProvider;
use Modules\Shared\Contracts\PluginApi;

require_once __DIR__.'/plugin-api.php';

/*
| API cho plugin (ADR-048): class #[PluginApi], PluginServiceProvider, class hỗ trợ (PLUGIN_PUBLIC_SUPPORT), helper vani_*
| và hook public được chụp vào plugin-api.snapshot với version `vanishop.extension_api` — tách khỏi version Core.
|
| Đổi bề mặt → test đỏ. Khi thay đổi là có chủ đích:
|   1. tăng `vanishop.extension_api`: xoá/đổi chữ ký phần STABLE → tăng major; còn lại (thêm, đổi phần experimental,
|      experimental → stable) → tăng minor. Ghi "Extension API x.y.z" trong docs/04-extension/CHANGELOG-extension.md
|   2. chạy lại với VANI_UPDATE_API_SNAPSHOT=1 — test từ chối ghi khi mức tăng không đủ.
*/

/**
 * Bề mặt theo khối: tên khối => [stability, dòng]. Khối là một class, PluginServiceProvider, một helper hoặc một hook.
 *
 * @return array<string, array{0: string, 1: list<string>}>
 */
function pluginApiSurface(): array
{
    $blocks = [];
    foreach (pluginApiClasses() as $class => $stability) {
        $lines = publicApiLines(new ReflectionClass($class));
        $lines[0] .= $stability === PluginApi::EXPERIMENTAL ? ' [experimental]' : '';
        $blocks[$class] = [$stability, $lines];
    }
    foreach ([PluginServiceProvider::class, ...PLUGIN_PUBLIC_SUPPORT] as $class) {
        $blocks[$class] = [PluginApi::STABLE, publicApiLines(new ReflectionClass($class))];
    }

    $helperFiles = array_map(fn (string $file): string => str_replace('\\', '/', $file), glob(base_path('modules/*/helpers.php')) ?: []);
    foreach (get_defined_functions()['user'] as $function) {
        $reflection = new ReflectionFunction($function);
        if (in_array(str_replace('\\', '/', (string) $reflection->getFileName()), $helperFiles, true)) {
            $return = $reflection->getReturnType() === null ? '' : ': '.$reflection->getReturnType();
            $blocks["function {$reflection->getName()}"] = [PluginApi::STABLE, ["function {$reflection->getName()}(".implode(', ', array_map('publicApiSignature', $reflection->getParameters())).")$return"]];
        }
    }

    foreach (glob(base_path('modules/*/hooks.php')) ?: [] as $file) {
        foreach ((array) require $file as $name => $hook) {
            if (($hook['visibility'] ?? 'public') !== 'public') {
                continue;
            }
            $stability = ($hook['stability'] ?? PluginApi::STABLE) === PluginApi::EXPERIMENTAL ? PluginApi::EXPERIMENTAL : PluginApi::STABLE;
            $args = implode(', ', array_map(fn (string $arg, mixed $type): string => "{$arg}: ".(is_string($type) ? $type : 'mixed'), array_keys((array) ($hook['args'] ?? [])), (array) ($hook['args'] ?? [])));
            $blocks["hook {$name}"] = [$stability, ["hook {$name} {$hook['type']}({$args})".($stability === PluginApi::EXPERIMENTAL ? ' [experimental]' : '')]];
        }
    }
    ksort($blocks);

    return $blocks;
}

/**
 * @param  array<string, array{0: string, 1: list<string>}>  $blocks
 */
function pluginApiText(array $blocks): string
{
    return implode("\n", array_merge(...array_map(fn (array $block): array => $block[1], array_values($blocks))))."\n";
}

/**
 * Snapshot đã ghi → khối (dòng không thụt lề mở khối mới; "[experimental]" ở dòng đầu là mức của khối).
 *
 * @return array<string, array{0: string, 1: list<string>}>
 */
function pluginApiParse(string $body): array
{
    $blocks = [];
    $current = null;
    foreach (explode("\n", rtrim($body, "\n")) as $line) {
        if ($line === '') {
            continue;
        }
        if (! str_starts_with($line, ' ')) {
            $current = $line;
            $blocks[$current] = [str_ends_with($line, '[experimental]') ? PluginApi::EXPERIMENTAL : PluginApi::STABLE, [$line]];

            continue;
        }
        $blocks[$current][1][] = $line;
    }

    return $blocks;
}

/**
 * Mức tăng tối thiểu: 'major' nếu dòng của khối stable bị xoá/đổi, 'minor' nếu có thay đổi khác, null nếu không đổi.
 */
function pluginApiRequiredBump(string $recordedBody, string $currentBody): ?string
{
    if ($recordedBody === $currentBody) {
        return null;
    }
    $current = array_flip(explode("\n", $currentBody));
    foreach (pluginApiParse($recordedBody) as [$stability, $lines]) {
        if ($stability === PluginApi::STABLE && array_diff_key(array_flip($lines), $current) !== []) {
            return 'major';
        }
    }

    return 'minor';
}

it('API cho plugin khớp snapshot; đổi phải tăng extension_api đúng mức và ghi CHANGELOG', function () {
    $snapshot = __DIR__.'/plugin-api.snapshot';
    $version = (string) config('vanishop.extension_api');
    $body = pluginApiText(pluginApiSurface());
    $recorded = is_file($snapshot) ? (string) file_get_contents($snapshot) : '';
    [$recordedHeader, $recordedBody] = array_pad(explode("\n", $recorded, 2), 2, '');
    $recordedVersion = substr($recordedHeader, strlen('Extension API '));

    if (getenv('VANI_UPDATE_API_SNAPSHOT') === '1' || $recorded === '') {
        $bump = $recorded === '' ? null : pluginApiRequiredBump($recordedBody, $body);
        // Giai đoạn dev (vanishop.extension_api_stage): API chưa chốt — thay đổi phá vỡ chỉ cần tăng minor; plugin trong repo
        // cập nhật theo (test của plugin chạy cùng bộ test nên bắt được chỗ chưa cập nhật).
        if ($bump === 'major' && config('vanishop.extension_api_stage') === 'dev') {
            $bump = 'minor';
        }
        if ($bump !== null) {
            [$oldMajor, $oldMinor] = array_map('intval', explode('.', $recordedVersion));
            [$newMajor, $newMinor] = array_map('intval', explode('.', $version));
            $enough = $bump === 'major' ? $newMajor > $oldMajor : ($newMajor > $oldMajor || ($newMajor === $oldMajor && $newMinor > $oldMinor));
            expect($enough)->toBeTrue("API cho plugin đổi cần tăng {$bump} của vanishop.extension_api (đang {$recordedVersion} → {$version})");
        }
        file_put_contents($snapshot, "Extension API {$version}\n".$body);
        [$recordedHeader, $recordedBody] = explode("\n", (string) file_get_contents($snapshot), 2);
        $recordedVersion = substr($recordedHeader, strlen('Extension API '));
    }

    expect($recordedBody)->toBe($body, 'API cho plugin đổi: tăng vanishop.extension_api, ghi CHANGELOG-extension, chạy lại với VANI_UPDATE_API_SNAPSHOT=1')
        ->and($recordedVersion)->toBe($version, 'vanishop.extension_api khác version trong plugin-api.snapshot')
        ->and(str_contains((string) file_get_contents(base_path('docs/04-extension/CHANGELOG-extension.md')), "Extension API {$version}"))
        ->toBeTrue("CHANGELOG-extension thiếu ghi chú \"Extension API {$version}\"");
});

it('xếp loại thay đổi: xoá/đổi phần stable → major; thêm hoặc đổi phần experimental → minor', function () {
    $old = "interface A\n  run(): void\ninterface B [experimental]\n  go(): void\n";

    expect(pluginApiRequiredBump($old, $old))->toBeNull()
        ->and(pluginApiRequiredBump($old, $old."interface C\n"))->toBe('minor')
        ->and(pluginApiRequiredBump($old, "interface A\n  run(): void\ninterface B [experimental]\n  go(int \$x): void\n"))->toBe('minor')
        ->and(pluginApiRequiredBump($old, "interface A\n  run(int \$x): void\ninterface B [experimental]\n  go(): void\n"))->toBe('major')
        ->and(pluginApiRequiredBump($old, "interface A\n  run(): void\ninterface B\n  go(): void\n"))->toBe('minor')
        ->and(pluginApiRequiredBump($old, "interface A [experimental]\n  run(): void\ninterface B [experimental]\n  go(): void\n"))->toBe('major');
});

it('P7: plugin trong repo khai requires.extension_api theo major hiện tại, không khai requires.vanishop lỏng', function () {
    $major = (int) explode('.', (string) config('vanishop.extension_api'))[0];
    $problems = [];
    foreach (glob(base_path('custom/plugin/*/vanishop.json')) ?: [] as $file) {
        $requires = (array) (json_decode((string) file_get_contents($file), true)['requires'] ?? []);
        $constraint = (string) ($requires['extension_api'] ?? '');
        if (preg_match('/^\^'.$major.'\.\d+$/', $constraint) !== 1) {
            $problems[] = basename(dirname($file)).": requires.extension_api [{$constraint}] phải là ^{$major}.x";
        }
        if (isset($requires['vanishop'])) {
            $problems[] = basename(dirname($file)).': bỏ requires.vanishop (chỉ khai khi cần bản sửa lỗi cụ thể của Core, kèm lý do trong README)';
        }
    }

    expect($problems)->toBe([]);
});
