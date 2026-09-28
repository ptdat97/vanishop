<?php

/*
| Architecture rules — docs/01-principles/architecture-rules.md
*/

$modules = require __DIR__.'/../../config/modules.php';

arch('R4: Core không phụ thuộc Plugin')
    ->expect('Modules')
    ->not->toUse('Plugin');

arch('R4: app/ không phụ thuộc Plugin')
    ->expect('App')
    ->not->toUse('Plugin');

foreach ($modules as $module) {
    arch("R8: Domain của {$module} không phụ thuộc framework/hạ tầng")
        ->expect("Modules\\{$module}\\Domain")
        ->not->toUse(['Illuminate\\Database', 'Illuminate\\Support\\Facades', 'Illuminate\\Http', 'Inertia']);

    arch("R9: Controller của {$module} không chứa logic persistence trực tiếp qua DB facade")
        ->expect("Modules\\{$module}\\Http\\Controllers")
        ->not->toUse('Illuminate\\Support\\Facades\\DB');
}

// Plugin chỉ được dùng Contracts/, Events/ và PluginServiceProvider — không dùng tầng nội bộ của module.
$internalLayers = [];
foreach ($modules as $module) {
    foreach (['Application', 'Persistence', 'Infrastructure', 'Domain'] as $layer) {
        $internalLayers[] = "Modules\\{$module}\\{$layer}";
    }
}

arch('R5: Plugin chỉ dùng API public của Core')
    ->expect('Plugin')
    ->not->toUse($internalLayers);

arch('Module chỉ dùng Contracts của module khác (Identity → Brand)')
    ->expect('Modules\\Identity')
    ->not->toUse(['Modules\\Brand\\Persistence', 'Modules\\Brand\\Application']);

arch('Class của module dùng strict types')
    ->expect('Modules')
    ->toUseStrictTypes()
    ->ignoring('Modules\\Extension\\Tests');

arch('Không dùng hàm debug')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();
