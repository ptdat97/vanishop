<?php

use Illuminate\Support\Facades\Route;
use Modules\Fulfillment\Http\Controllers\Api\CarrierWebhookController;

// Prefix: /api/shipping — tên route: api.shipping.*
Route::post('{carrier}/webhook', CarrierWebhookController::class)->where('carrier', '[a-z0-9_.-]+')->name('webhook');
