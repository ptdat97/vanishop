<?php

use Illuminate\Support\Facades\Route;
use Plugin\PricingCampaign\Http\Controllers\CampaignPageController;

Route::get('/', CampaignPageController::class)->name('index');
