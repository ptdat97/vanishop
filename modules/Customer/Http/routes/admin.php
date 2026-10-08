<?php

use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\Admin\CustomerController;
use Modules\Customer\Http\Controllers\Admin\CustomerGroupController;

Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
Route::get('customer-groups', [CustomerGroupController::class, 'index'])->name('customer-groups.index');
Route::post('customer-groups', [CustomerGroupController::class, 'store'])->name('customer-groups.store');
Route::put('customer-groups/{group}', [CustomerGroupController::class, 'update'])->whereNumber('group')->name('customer-groups.update');
Route::delete('customer-groups/{group}', [CustomerGroupController::class, 'destroy'])->whereNumber('group')->name('customer-groups.destroy');
Route::put('customers/{customer}/segment', [CustomerController::class, 'segment'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.segment');
Route::get('customers/{customer}', [CustomerController::class, 'show'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.show');
Route::post('customers/{customer}/merge', [CustomerController::class, 'merge'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.merge');
Route::post('customers/{customer}/anonymize', [CustomerController::class, 'anonymize'])->where('customer', '[0-9A-Za-z]{26}')->name('customers.anonymize');
