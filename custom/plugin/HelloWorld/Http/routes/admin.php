<?php

use Illuminate\Support\Facades\Route;
use Plugin\HelloWorld\Http\Controllers\HelloController;

Route::get('/', HelloController::class)->name('index');
