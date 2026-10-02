<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// GET /api/storefront/v1/x/vani-hello-world/greeting?name=Lan
Route::get('greeting', function (Request $request) {
    $name = trim((string) $request->validate(['name' => ['nullable', 'string', 'max:60']])['name'] ?? '');

    return response()->json(['data' => ['message' => $name === '' ? 'Xin chào!' : "Xin chào, {$name}!"]]);
})->name('greeting');
