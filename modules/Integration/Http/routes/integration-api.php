<?php

use Illuminate\Support\Facades\Route;
use Modules\Integration\Http\Controllers\Api\IntegrationApiController;

// Prefix: /api/integration/v1 — tên route: api.integration.v1.*
Route::get('events', [IntegrationApiController::class, 'events'])->middleware('vani.integration-scope:events:read')->name('events');
Route::get('orders', [IntegrationApiController::class, 'orders'])->middleware('vani.integration-scope:orders:read')->name('orders.index');
Route::get('orders/{number}', [IntegrationApiController::class, 'order'])->middleware('vani.integration-scope:orders:read')->name('orders.show');
Route::post('orders/{number}/acknowledgements', [IntegrationApiController::class, 'acknowledge'])->middleware('vani.integration-scope:orders:write')->name('orders.acknowledgements');
Route::put('inventory/levels', [IntegrationApiController::class, 'inventoryLevels'])->middleware('vani.integration-scope:inventory:write')->name('inventory.levels');
