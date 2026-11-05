<?php

use Illuminate\Support\Facades\File;

it('vani:docs:schema sinh từ điển dữ liệu bảng Core (bỏ plg_*); --check báo lệch', function () {
    $path = 'storage/framework/testing/schema-'.uniqid().'.md';

    $this->artisan('vani:docs:schema', ['--path' => $path])->assertSuccessful();
    $markdown = File::get(base_path($path));
    expect($markdown)->toContain('## carts')->toContain('`public_id`')->toContain('## style_view_stats')->not->toContain('## plg_');

    $this->artisan('vani:docs:schema', ['--path' => $path, '--check' => true])->assertSuccessful();
    File::put(base_path($path), $markdown.'sửa tay');
    $this->artisan('vani:docs:schema', ['--path' => $path, '--check' => true])->assertFailed();

    File::delete(base_path($path));
});
