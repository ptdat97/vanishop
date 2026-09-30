<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Admin\CustomerController;

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.show');
Route::post('customers/{customer}/merge', [CustomerController::class, 'merge'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.merge');
Route::post('customers/{customer}/anonymize', [CustomerController::class, 'anonymize'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.anonymize');
