<?php

use Modules\Extension\Testing\PluginHealthCheckContract;
use Modules\Shared\Testing\AddressDirectoryContract;
use Plugin\ProvincesVn\Infrastructure\DivisionsHealthCheck;
use Plugin\ProvincesVn\Infrastructure\VnDivisions;

/*
| Củng cố kiến trúc K3 (P4, R26): implementation tham chiếu của plugin chạy bộ contract test của Core.
*/

// Plugin hệ thống (bundled): đã cài + bật sẵn trong môi trường test.

AddressDirectoryContract::define('vani.provinces-vn', fn () => app(VnDivisions::class));
PluginHealthCheckContract::define('vani.provinces-vn', fn () => app(DivisionsHealthCheck::class));
