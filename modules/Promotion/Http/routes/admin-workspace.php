<?php

use Illuminate\Support\Facades\Route;
use Modules\Promotion\Http\Controllers\Admin\PromotionController;

// Prefix: /{admin}/promotion — tên route: admin.promotion.*
Route::resource('promotions', PromotionController::class)->except('show');
Route::post('promotions/{promotion}/vouchers', [PromotionController::class, 'storeVouchers'])->name('promotions.vouchers.store');
Route::patch('promotions/{promotion}/vouchers/{voucher}', [PromotionController::class, 'updateVoucher'])->name('promotions.vouchers.update');
