<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Catalog\Application\Search\DatabaseSearchProvider;
use Modules\Catalog\Application\Search\ProductDocumentBuilder;
use Modules\Catalog\Application\Search\SearchManager;
use Modules\Catalog\Contracts\ListPrices;
use Modules\Catalog\Persistence\Models\Style;
use Throwable;

/**
 * Ghi `styles.list_price_min` (bản sao giá niêm yết thấp nhất cho tìm kiếm). Không tăng `lock_version`: đây không phải
 * thao tác sửa sản phẩm của nhân viên, không được làm form sản phẩm đang mở báo "bản ghi đã đổi".
 */
final class StyleListPrices implements ListPrices
{
    public function __construct(
        private readonly SearchManager $search,
        private readonly ProductDocumentBuilder $documents,
    ) {}

    public function setMinimums(array $minimumByStyle): void
    {
        if ($minimumByStyle === []) {
            return;
        }

        $current = DB::table('styles')->whereIn('id', array_keys($minimumByStyle))->pluck('list_price_min', 'id');
        $changed = [];
        foreach ($minimumByStyle as $styleId => $minimum) {
            if (! $current->has($styleId)) {
                continue;
            }
            $before = $current->get($styleId);
            if (($before === null ? null : (int) $before) !== $minimum) {
                DB::table('styles')->where('id', $styleId)->update(['list_price_min' => $minimum]);
                $changed[] = (int) $styleId;
            }
        }

        $this->reindex($changed);
    }

    /**
     * Provider có chỉ mục ngoài cần tài liệu mới; provider `database` đọc thẳng cột vừa ghi.
     *
     * @param  list<int>  $styleIds
     */
    private function reindex(array $styleIds): void
    {
        $provider = $this->search->provider();
        if ($styleIds === [] || $provider instanceof DatabaseSearchProvider) {
            return;
        }

        foreach (Style::query()->whereIn('id', $styleIds)->get() as $style) {
            try {
                $provider->index($this->documents->build($style));
            } catch (Throwable $exception) {
                Log::warning('Không cập nhật được giá niêm yết vào chỉ mục tìm kiếm.', ['style' => $style->id, 'provider' => $provider->code(), 'exception' => $exception]);
            }
        }
    }
}
