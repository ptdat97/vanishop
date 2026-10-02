<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelSetList;

/*
| Rector (ADR-032): chạy có chủ đích (`composer rector` để xem, `composer rector:fix` để áp dụng), không tự động trong CI.
| Bắt đầu với bộ quy tắc an toàn: chất lượng code, kiểu khai báo, nâng cú pháp PHP 8.4, quy ước Laravel.
*/

return RectorConfig::configure()
    ->withPaths([__DIR__.'/app', __DIR__.'/modules', __DIR__.'/custom/plugin'])
    ->withSkip([__DIR__.'/modules/*/Persistence/Database/migrations', __DIR__.'/custom/plugin/*/Database/migrations'])
    ->withPhpSets(php84: true)
    ->withPreparedSets(deadCode: true, codeQuality: true, typeDeclarations: true)
    ->withSets([LaravelSetList::LARAVEL_CODE_QUALITY]);
