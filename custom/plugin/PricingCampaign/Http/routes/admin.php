<?php

use Illuminate\Support\Facades\Route;
use Plugin\PricingCampaign\Http\Controllers\Admin\CampaignController;

Route::get('/', [CampaignController::class, 'index'])->name('index');
Route::get('create', [CampaignController::class, 'create'])->name('create');
Route::get('products', [CampaignController::class, 'products'])->name('products');
Route::get('categories', [CampaignController::class, 'categories'])->name('categories');
Route::post('/', [CampaignController::class, 'store'])->name('store');
Route::get('{campaign}', [CampaignController::class, 'edit'])->whereNumber('campaign')->name('edit');
Route::put('{campaign}', [CampaignController::class, 'update'])->whereNumber('campaign')->name('update');
Route::delete('{campaign}', [CampaignController::class, 'destroy'])->whereNumber('campaign')->name('destroy');
