<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Modules\Extension\Facades\Hook;

// GET /api/storefront/v1/x/vani-hello-world/greeting?name=Lan
Route::get('greeting', function (Request $request) {
    $name = trim((string) $request->validate(['name' => ['nullable', 'string', 'max:60']])['name'] ?? '');

    $message = $name === '' ? 'Xin chào!' : "Xin chào, {$name}!";

    return response()->json(['data' => ['message' => Hook::filter('vani.hello-world.greeting', $message, $name)]]);
})->name('greeting');
