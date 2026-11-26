<?php

declare(strict_types=1);

namespace Modules\Catalog\Console;

use Illuminate\Console\Command;
use Modules\Catalog\Application\Products\ProductViewCounter;

/**
 * Ghi lượt xem sản phẩm đang đếm trong cache xuống `product_view_stats` (mỗi phút); `--compact`: gộp dữ liệu theo giờ cũ
 * hơn 30 ngày thành theo ngày (hằng đêm). Chạy lại an toàn.
 */
final class FlushViewsCommand extends Command
{
    protected $signature = 'vani:catalog:flush-views {--compact : Gộp dữ liệu theo giờ cũ hơn 30 ngày thành theo ngày}';

    protected $description = 'Ghi lượt xem sản phẩm từ cache xuống DB (thống kê lượt xem)';

    public function handle(ProductViewCounter $views): int
    {
        if ($this->option('compact')) {
            $this->info('Đã gộp '.$views->compact().' dòng theo giờ.');

            return self::SUCCESS;
        }
        $this->info('Đã ghi '.$views->flush().' lượt xem.');

        return self::SUCCESS;
    }
}
