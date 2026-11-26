<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Customer\Persistence\Models\Customer;
use Modules\Shared\Support\DeferredPage;

/*
| Deferred join (db-refactor L54) cho đúng trang, đúng thứ tự, đúng tổng như paginate() — kể cả khi thứ tự có giá trị trùng.
*/

it('cho cùng kết quả với paginate() ở mọi trang và cả khi thứ tự trùng giá trị', function (string $direction) {
    foreach (range(1, 23) as $i) {
        DB::table('customers')->insert(['public_id' => (string) Str::ulid(), 'phone' => sprintf('+8490000%04d', $i), 'full_name' => 'Khách '.($i % 4), 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    }
    $query = fn () => Customer::query()->orderBy('full_name', $direction)->orderBy('id', $direction);

    foreach ([1, 2, 3, 4] as $page) {
        app()->instance('request', Request::create('/admin/customers', 'GET', ['page' => $page]));
        $expected = $query()->paginate(7);
        $actual = DeferredPage::paginate($query(), 7);

        expect($actual->pluck('id')->all())->toBe($expected->pluck('id')->all())
            ->and($actual->total())->toBe(23)->and($actual->lastPage())->toBe(4);
    }
    expect(DeferredPage::paginate($query(), 7, total: 99)->total())->toBe(99); // tổng có sẵn thì không đếm lại
})->with(['asc', 'desc']);
