<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Http\Controllers\Admin\CampaignController;
use Modules\Promotion\Http\Controllers\Admin\PromotionController;

// Prefix: /{admin}/promotion — tên route: admin.promotion.*
Route::resource('promotions', PromotionController::class)->except('show');
Route::resource('campaigns', CampaignController::class)->except('show');
Route::post('campaigns/{campaign}/activate', [CampaignController::class, 'activate'])->name('campaigns.activate');
Route::post('campaigns/{campaign}/stop', [CampaignController::class, 'stop'])->name('campaigns.stop');
Route::post('promotions/{promotion}/vouchers', [PromotionController::class, 'storeVouchers'])->name('promotions.vouchers.store');
Route::patch('promotions/{promotion}/vouchers/{voucher}', [PromotionController::class, 'updateVoucher'])->name('promotions.vouchers.update');
