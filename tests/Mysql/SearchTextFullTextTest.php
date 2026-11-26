<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Application\Search\SearchTextMatch;
use Modules\Catalog\Persistence\Models\Product;
use Modules\Shared\Domain\Text\VietnameseText;
use Tests\Support\KeepsReferenceData;

/*
| Tìm chữ bằng FULLTEXT ngram (db-refactor §4.1) cho cùng kết quả như LIKE '%…%' trước đây: từ 2 ký tự, chuỗi con của mã,
| từ chứa bigram nằm trong stopword mặc định của InnoDB ("la", "an"), token 1 ký tự.
*/

uses(DatabaseTruncation::class, KeepsReferenceData::class);

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Cần MySQL (FULLTEXT ngram).');
    }
    foreach (['ao so mi lua trang bp000123', 'quan jean xanh bp000124', 'ao khoac lanh an toan bp000125', 'dam xep ly bp000200'] as $index => $text) {
        Product::factory()->create(['product_code' => 'BP'.(123 + $index), 'search_text' => $text]);
    }
});

it('khớp như LIKE: từ ngắn, chuỗi con của mã, bigram stopword, token 1 ký tự', function (string $text, array $expected) {
    $match = Product::query();
    SearchTextMatch::apply($match, VietnameseText::tokens($text), 'search_text');
    $like = Product::query();
    foreach (VietnameseText::tokens($text) as $token) {
        $like->where('search_text', 'like', '%'.$token.'%');
    }

    expect($match->orderBy('id')->pluck('search_text')->all())->toBe($expected)
        ->and($like->orderBy('id')->pluck('search_text')->all())->toBe($expected)
        ->and($match->toSql())->toContain('match');
})->with([
    'áo sơ mi' => ['áo sơ mi', ['ao so mi lua trang bp000123']],
    'mã một phần' => ['BP0001', ['ao so mi lua trang bp000123', 'quan jean xanh bp000124', 'ao khoac lanh an toan bp000125']],
    'bigram stopword' => ['lạnh an', ['ao khoac lanh an toan bp000125']],
    'token 1 ký tự' => ['áo y', []],
    'xếp ly' => ['xếp ly', ['dam xep ly bp000200']],
]);
