<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

/**
 * Store cache cho dữ liệu tạm **phải giữ tới khi hết hạn**: token xác minh email, đặt lại mật khẩu, đăng ký, state đăng nhập
 * mạng xã hội, bản xem trước khi nhập file, bộ đếm chờ ghi xuống DB, tiến độ thao tác hàng loạt. Mất sớm là sai nghiệp vụ
 * (khách bấm link bị báo hết hạn, mất lượt xem), nên không để ở store cache thường — store đó có thể là Redis allkeys-lru và
 * bị `cache:clear` khi deploy. Mặc định bảng `cache_durable` (`config('cache.durable_store')`); dòng hết hạn dọn bằng
 * `vani:cache:prune-durable`. Dữ liệu tính lại được (số đếm, facet, menu) vẫn dùng store mặc định.
 */
final class DurableCache
{
    public static function store(): Repository
    {
        return Cache::store((string) config('cache.durable_store', 'durable'));
    }
}
