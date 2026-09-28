<?php

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\Admin\ReturnController;

Route::get('returns', [ReturnController::class, 'home'])->name('returns.home');
