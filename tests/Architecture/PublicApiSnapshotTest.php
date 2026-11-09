<?php

use Modules\Extension\PluginServiceProvider;

/*
| Public API cho plugin (docs/04-extension/extension-model.md §5): chữ ký của mọi thứ trong
| Modules\*\Contracts, Modules\*\Events và PluginServiceProvider được chụp vào public-api.snapshot.
|
| Đổi public API → test đỏ. Khi thay đổi là có chủ đích:
|   1. xếp loại (thêm = minor, đổi/xoá = major — với 0.x: tăng số giữa) và ghi docs/04-extension/CHANGELOG-extension.md
|   2. chạy lại với VANI_UPDATE_API_SNAPSHOT=1 để ghi snapshot mới
*/

function publicApiSignature(ReflectionParameter $parameter): string
{
    $type = $parameter->getType() === null ? '' : $parameter->getType().' ';

    return $type.($parameter->isVariadic() ? '...' : '').'$'.$parameter->getName().($parameter->isDefaultValueAvailable() ? ' = '.var_export($parameter->getDefaultValue(), true) : '');
}

function publicApiLines(ReflectionClass $class): array
{
    $kind = $class->isInterface() ? 'interface' : ($class->isEnum() ? 'enum' : ($class->isAbstract() ? 'abstract class' : 'class'));
    $lines = ["{$kind} {$class->getName()}".($class->getParentClass() ? ' extends '.$class->getParentClass()->getName() : '')];

    foreach ($class->getReflectionConstants() as $constant) {
        if ($constant->isPublic() && $constant->getDeclaringClass()->getName() === $class->getName()) {
            $lines[] = "  const {$constant->getName()} = ".var_export($constant->getValue(), true);
        }
    }

    $isPluginBase = $class->getName() === PluginServiceProvider::class;
    foreach ($class->getMethods() as $method) {
        $visible = $method->isPublic() || ($isPluginBase && $method->isProtected());
        if (! $visible || $method->getDeclaringClass()->getName() !== $class->getName()) {
            continue;
        }
        $parameters = implode(', ', array_map('publicApiSignature', $method->getParameters()));
        $return = $method->getReturnType() === null ? '' : ': '.$method->getReturnType();
        $lines[] = '  '.($method->isStatic() ? 'static ' : '').($method->isProtected() ? 'protected ' : '')."{$method->getName()}({$parameters}){$return}";
    }

    return $lines;
}

it('public API cho plugin khớp snapshot (đổi API phải ghi CHANGELOG-extension + cập nhật snapshot)', function () {
    $files = [];
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('modules'))) as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (str_ends_with($path, '.php') && preg_match('#/modules/[^/]+/(Contracts|Events)/#', $path) === 1) {
            $files[] = $path;
        }
    }
    $files[] = str_replace('\\', '/', base_path('modules/Extension/PluginServiceProvider.php'));
    sort($files);

    $lines = [];
    $modulesPath = rtrim(str_replace('\\', '/', base_path('modules')), '/').'/';
    foreach ($files as $path) {
        $class = 'Modules\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($modulesPath)));
        if (class_exists($class) || interface_exists($class) || enum_exists($class)) {
            array_push($lines, ...publicApiLines(new ReflectionClass($class)));
        }
    }

    // Helper toàn cục cho plugin/theme (ADR-036, ADR-037) cũng là public API.
    $helperFile = str_replace('\\', '/', base_path('modules/Extension/helpers.php'));
    $helpers = array_filter(get_defined_functions()['user'], fn (string $function): bool => str_replace('\\', '/', (new ReflectionFunction($function))->getFileName()) === $helperFile);
    sort($helpers);
    foreach ($helpers as $function) {
        $reflection = new ReflectionFunction($function);
        $return = $reflection->getReturnType() === null ? '' : ': '.$reflection->getReturnType();
        $lines[] = "function {$reflection->getName()}(".implode(', ', array_map('publicApiSignature', $reflection->getParameters())).")$return";
    }

    $snapshot = __DIR__.'/public-api.snapshot';
    $current = 'VaniShop '.config('vanishop.version')."\n".implode("\n", $lines)."\n";
    if (getenv('VANI_UPDATE_API_SNAPSHOT') === '1' || ! is_file($snapshot)) {
        file_put_contents($snapshot, $current);
    }

    expect($current)->toBe(file_get_contents($snapshot));
});
