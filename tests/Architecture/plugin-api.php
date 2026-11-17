<?php

use Modules\Shared\Contracts\PluginApi;

/*
| Dùng chung cho các test kiến trúc về API công khai (PublicApiSnapshotTest, PluginApiSnapshotTest, ArchitectureTest R5).
*/

if (! defined('PLUGIN_PUBLIC_SUPPORT')) {
    /** Class hỗ trợ ngoài Contracts/Events mà plugin được dùng: giờ/ngôn ngữ/tiền/URL của cửa hàng và facade hook. */
    define('PLUGIN_PUBLIC_SUPPORT', [
        'Modules\\Extension\\Facades\\Hook',
        'Modules\\Shared\\Support\\LocalizedUrls',
        'Modules\\Shared\\Support\\MoneyFormatter',
        // EP v3 X1: plugin đọc file tải lên qua adminTool (nhập sao kê, nhập tồn…).
        'Modules\\Shared\\Support\\SpreadsheetRows',
        'Modules\\Shared\\Support\\StoreClock',
        'Modules\\Shared\\Support\\StoreLocale',
    ]);
}

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

/**
 * Class `Contracts`/`Events` mang #[PluginApi] — API cho plugin (ADR-048).
 *
 * @return array<string, string> tên class => stable|experimental
 */
function pluginApiClasses(): array
{
    $classes = [];
    $modulesPath = rtrim(str_replace('\\', '/', base_path('modules')), '/').'/';
    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(base_path('modules'), FilesystemIterator::SKIP_DOTS)) as $file) {
        $path = str_replace('\\', '/', (string) $file);
        if (! str_ends_with($path, '.php') || preg_match('#/modules/[^/]+/(Contracts|Events)/#', $path) !== 1) {
            continue;
        }
        $class = 'Modules\\'.str_replace(['/', '.php'], ['\\', ''], substr($path, strlen($modulesPath)));
        if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
            continue;
        }
        $attributes = (new ReflectionClass($class))->getAttributes(PluginApi::class);
        if ($attributes !== []) {
            $classes[$class] = $attributes[0]->newInstance()->stability;
        }
    }
    ksort($classes);

    return $classes;
}
