<?php

use Illuminate\Support\Facades\Route;
use Modules\Extension\Http\Controllers\Admin\PluginController;

Route::get('plugins', [PluginController::class, 'index'])->name('plugins.index');
