<?php

use Illuminate\Support\Facades\DB;

it('lệnh đo hiệu năng từ chối chạy khi DB không phải MySQL *_bench', function (string $command) {
    $this->artisan($command)->assertFailed();

    expect(DB::table('products')->count())->toBe(0);
})->with(['vani:bench:seed', 'vani:bench:measure']);
