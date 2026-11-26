<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Lọc theo `products.search_text` (văn bản không dấu, ProductService ghi khi lưu) — mọi token phải có mặt (AND).
 *
 * MySQL: chỉ mục FULLTEXT `ft_products_search` parser `ngram` (db-refactor §4.1): mỗi token ≥ 2 ký tự là một cụm `+"…"`
 * trong `MATCH … AGAINST (… IN BOOLEAN MODE)` — tìm được chuỗi con như LIKE ("so mi", mã một phần) mà không quét bảng.
 * Token 1 ký tự (ngram 2 ký tự không khớp được) và SQLite (test) dùng `LIKE '%…%'`.
 */
final class SearchTextMatch
{
    /**
     * @param  list<string>  $tokens  từ `VietnameseText::tokens()` (chữ thường, không dấu, chỉ chữ và số)
     */
    public static function apply(Builder $query, array $tokens, string $column = 'products.search_text'): void
    {
        $fullText = [];
        foreach ($tokens as $token) {
            $clean = (string) preg_replace('/[^\p{L}\p{N}]+/u', '', $token);
            if ($clean === '') {
                continue;
            }
            if (DB::connection()->getDriverName() === 'mysql' && mb_strlen($clean) >= 2) {
                $fullText[] = '+"'.$clean.'"';
            } else {
                $query->where($column, 'like', '%'.addcslashes($clean, '%_\\').'%');
            }
        }
        if ($fullText !== []) {
            $query->whereRaw("match({$column}) against (? in boolean mode)", [implode(' ', $fullText)]);
        }
    }
}
