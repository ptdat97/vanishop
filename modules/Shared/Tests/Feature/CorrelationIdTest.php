<?php

use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::get('/__test/correlation', fn () => response()->json(['id' => Context::get('correlation_id')]));
});

it('sinh correlation id và trả về trong header', function () {
    $response = $this->get('/__test/correlation');

    $id = $response->headers->get('X-Correlation-Id');
    expect($id)->toMatch('/^[0-9A-Z]{26}$/');
    $response->assertJson(['id' => $id]);
});

it('giữ correlation id hợp lệ từ client', function () {
    $this->get('/__test/correlation', ['X-Correlation-Id' => 'client-trace-123'])
        ->assertHeader('X-Correlation-Id', 'client-trace-123')
        ->assertJson(['id' => 'client-trace-123']);
});

it('thay correlation id không hợp lệ bằng id mới', function () {
    $response = $this->get('/__test/correlation', ['X-Correlation-Id' => "bad id\n<script>"]);

    expect($response->headers->get('X-Correlation-Id'))->not->toBe("bad id\n<script>")->toMatch('/^[0-9A-Z]{26}$/');
});
